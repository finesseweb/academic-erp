<?php

namespace App\Services;

use App\Models\College;
use App\Models\CollegeAdmissionApplicationChoice;
use App\Models\CollegeAdmissionMeritEntry;
use App\Models\CollegeAdmissionScore;
use App\Models\CollegeAdmissionSelectionRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CollegeAdmissionMeritService
{
    public function summary(College $college, CollegeAdmissionSelectionRule $rule): array
    {
        $this->assertRuleOwnedByCollege($college, $rule);
        $candidates = $this->candidateQuery($college, $rule)->get();
        $generated = CollegeAdmissionMeritEntry::query()
            ->where('college_admission_selection_rule_id', $rule->id)
            ->orderBy('rank')
            ->get();

        return $this->buildSummary($candidates, $generated);
    }

    public function preview(College $college, CollegeAdmissionSelectionRule $rule, int $limit = 100): array
    {
        $this->assertRuleOwnedByCollege($college, $rule);
        $generated = CollegeAdmissionMeritEntry::query()
            ->where('college_admission_selection_rule_id', $rule->id)
            ->with(['application:id,application_no,candidate_name,email,submitted_at,date_of_birth', 'application.academicPreference.discipline:id,name,code', 'application.academicPreference.specialization:id,name,code', 'score:id,merit_normalized_score,entrance_normalized_score,interview_normalized_score,final_weighted_score'])
            ->orderBy('rank')
            ->get();

        if ($generated->isNotEmpty()) {
            return [
                'mode' => 'GENERATED',
                'rows' => $generated->take($limit)->map(fn (CollegeAdmissionMeritEntry $entry) => $this->generatedRow($entry))->values()->all(),
                'total_ranked' => $generated->count(),
                'truncated' => $generated->count() > $limit,
                'generation_batch' => $generated->first()->generation_batch,
                'generated_at' => optional($generated->first()->generated_at)->toIso8601String(),
            ];
        }

        $candidates = $this->candidateQuery($college, $rule)->get();
        $classification = $this->classify($candidates);
        $ranked = $this->rankQualified($classification['qualified'], $rule);

        return [
            'mode' => 'PREVIEW',
            'rows' => $ranked->take($limit)->map(fn (array $row) => $this->publicRankedRow($row))->values()->all(),
            'total_ranked' => $ranked->count(),
            'truncated' => $ranked->count() > $limit,
            'generation_batch' => null,
            'generated_at' => null,
        ];
    }

    public function generate(College $college, CollegeAdmissionSelectionRule $rule, int $actorId, ?string $ip): Collection
    {
        $this->assertRuleOwnedByCollege($college, $rule);
        if (! in_array($rule->status, ['ACTIVE', 'RETIRED'], true)) {
            throw ValidationException::withMessages([
                'merit' => 'Merit / Roster can be generated only from a Selection Rule version that was operationally activated. INACTIVE draft rules cannot rank candidates.',
            ]);
        }

        return DB::transaction(function () use ($college, $rule, $actorId, $ip) {
            $alreadyGenerated = CollegeAdmissionMeritEntry::query()
                ->where('college_admission_selection_rule_id', $rule->id)
                ->lockForUpdate()
                ->exists();
            if ($alreadyGenerated) {
                throw ValidationException::withMessages([
                    'merit' => 'This exact locked Selection Rule version already has a generated Merit / Roster. Generated ranking is immutable; use the test cleanup flow only for disposable test data.',
                ]);
            }

            $choices = $this->candidateQuery($college, $rule, true)->get();
            $applicationIds = $choices->pluck('college_admission_application_id')->unique()->values();
            if ($applicationIds->isNotEmpty()) {
                DB::table('college_admission_applications')->whereIn('id', $applicationIds)->lockForUpdate()->get();
            }
            $scoreIds = $choices->pluck('score.id')->filter()->values();
            if ($scoreIds->isNotEmpty()) {
                CollegeAdmissionScore::query()->whereIn('id', $scoreIds)->lockForUpdate()->get();
            }
            // Refresh mutable upstream relations after their rows are locked so the
            // generated snapshot cannot consume stale Score/Application state.
            $choices->load([
                'application:id,college_id,application_no,candidate_name,email,date_of_birth,status,submitted_at',
                'application.fieldValues.field:id,field_key,label,field_type',
                'application.academicPreference.discipline:id,name,code',
                'application.academicPreference.specialization:id,name,code',
                'score',
            ]);

            if ($choices->contains(fn (CollegeAdmissionApplicationChoice $choice) => ! $choice->application || $choice->application->status !== 'SUBMITTED' || $choice->eligibility_status !== 'ELIGIBLE')) {
                throw ValidationException::withMessages(['merit' => 'Candidate eligibility/application state changed while Merit generation was starting. Refresh the page and generate again.']);
            }

            $classification = $this->classify($choices);
            if ($choices->isEmpty()) {
                throw ValidationException::withMessages(['merit' => 'No SUBMITTED + ELIGIBLE candidates are locked to this Selection Rule version.']);
            }
            if ($classification['pending']->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'merit' => $classification['pending']->count().' candidate(s) are still incomplete. Finish Score Capture and any required Interview evaluation before final Merit / Roster generation.',
                ]);
            }
            if ($classification['qualified']->isEmpty()) {
                throw ValidationException::withMessages(['merit' => 'No candidates currently qualify under this locked Selection Rule version, so there is no ranked roster to generate.']);
            }

            $rule->loadMissing('tieBreakers');
            if ($rule->tieBreakers->isEmpty()) {
                throw ValidationException::withMessages(['merit' => 'This Selection Rule does not contain structured tie-breakers. Final deterministic ranking cannot be generated.']);
            }

            $ranked = $this->rankQualified($classification['qualified'], $rule);
            $batch = (string) Str::uuid();
            $generatedAt = now();
            $created = collect();

            foreach ($ranked as $row) {
                /** @var CollegeAdmissionApplicationChoice $choice */
                $choice = $row['_choice'];
                $score = $choice->score;
                $created->push(CollegeAdmissionMeritEntry::create([
                    'college_id' => $college->id,
                    'college_program_intake_id' => $choice->college_program_intake_id,
                    'bucket_key' => $choice->bucket_key,
                    'college_admission_application_id' => $choice->college_admission_application_id,
                    'college_admission_application_choice_id' => $choice->id,
                    'college_admission_score_id' => $score->id,
                    'college_admission_selection_rule_id' => $rule->id,
                    'generation_batch' => $batch,
                    'rank' => $row['rank'],
                    'final_weighted_score' => $row['final_weighted_score'],
                    'tie_break_snapshot' => $row['tie_break_snapshot'],
                    'generated_at' => $generatedAt,
                    'generated_by' => $actorId,
                ]));
            }

            DB::table('audit_logs')->insert([
                'actor_user_id' => $actorId,
                'event' => 'COLLEGE_ADMISSION_MERIT_ROSTER_GENERATED',
                'resource_type' => 'CollegeAdmissionMeritRoster',
                'resource_id' => $rule->id,
                'scope_type' => 'COLLEGE',
                'scope_reference' => 'college:'.$college->id,
                'before' => null,
                'after' => json_encode([
                    'selection_rule_id' => $rule->id,
                    'selection_rule_code' => $rule->code,
                    'selection_rule_version' => $rule->version_no,
                    'generation_batch' => $batch,
                    'ranked_count' => $created->count(),
                    'not_qualified_count' => $classification['notQualified']->count(),
                    'ranked_choice_ids' => $created->pluck('college_admission_application_choice_id')->all(),
                ]),
                'ip_address' => $ip,
                'created_at' => $generatedAt,
            ]);

            return $created;
        });
    }

    private function candidateQuery(College $college, CollegeAdmissionSelectionRule $rule, bool $lock = false)
    {
        $query = CollegeAdmissionApplicationChoice::query()
            ->where('college_admission_selection_rule_id', $rule->id)
            ->where('eligibility_status', 'ELIGIBLE')
            ->whereHas('application', fn ($q) => $q->where('college_id', $college->id)->where('status', 'SUBMITTED'))
            ->with([
                'application:id,college_id,application_no,candidate_name,email,date_of_birth,status,submitted_at',
                'application.fieldValues.field:id,field_key,label,field_type',
                'application.academicPreference.discipline:id,name,code',
                'application.academicPreference.specialization:id,name,code',
                'score',
            ])
            ->orderBy('id');

        return $lock ? $query->lockForUpdate() : $query;
    }

    private function classify(Collection $choices): array
    {
        $qualified = collect();
        $notQualified = collect();
        $pending = collect();

        foreach ($choices as $choice) {
            $score = $choice->score;
            if (! $score || (int) $score->college_admission_selection_rule_id !== (int) $choice->college_admission_selection_rule_id) {
                $pending->push($choice);
                continue;
            }

            if ($score->qualification_status === 'NOT_QUALIFIED') {
                $notQualified->push($choice);
                continue;
            }

            if ($score->qualification_status !== 'QUALIFIED' || $score->final_weighted_score === null) {
                $pending->push($choice);
                continue;
            }

            $qualified->push($choice);
        }

        return compact('qualified', 'notQualified', 'pending');
    }

    private function buildSummary(Collection $candidates, Collection $generated): array
    {
        $classification = $this->classify($candidates);
        return [
            'total_candidates' => $candidates->count(),
            'qualified_count' => $classification['qualified']->count(),
            'not_qualified_count' => $classification['notQualified']->count(),
            'pending_count' => $classification['pending']->count(),
            'generated' => $generated->isNotEmpty(),
            'ranked_count' => $generated->count(),
            'generation_batch' => $generated->first()?->generation_batch,
            'generated_at' => optional($generated->first()?->generated_at)->toIso8601String(),
        ];
    }

    private function rankQualified(Collection $qualified, CollegeAdmissionSelectionRule $rule): Collection
    {
        $rule->loadMissing('tieBreakers');
        $rows = $qualified->map(function (CollegeAdmissionApplicationChoice $choice) use ($rule) {
            $score = $choice->score;
            $tieSnapshot = $rule->tieBreakers->map(function ($tie) use ($choice) {
                [$value, $source] = $this->tieValue($choice, $tie->criterion, $tie->criterion_reference);
                return [
                    'priority' => (int) $tie->priority,
                    'criterion' => $tie->criterion,
                    'direction' => $tie->comparison_direction,
                    'reference' => $tie->criterion_reference,
                    'value' => $value,
                    'source' => $source,
                ];
            })->values()->all();

            return [
                '_choice' => $choice,
                'choice_id' => $choice->id,
                'application_id' => $choice->college_admission_application_id,
                'application_no' => $choice->application->application_no,
                'candidate_name' => $choice->application->candidate_name,
                'discipline_name' => $choice->application->academicPreference?->discipline?->name,
                'discipline_code' => $choice->application->academicPreference?->discipline?->code,
                'specialization_name' => $choice->application->academicPreference?->specialization?->name,
                'specialization_code' => $choice->application->academicPreference?->specialization?->code,
                'final_weighted_score' => round((float) $score->final_weighted_score, 3),
                'merit_score' => $score->merit_normalized_score !== null ? round((float) $score->merit_normalized_score, 3) : null,
                'entrance_score' => $score->entrance_normalized_score !== null ? round((float) $score->entrance_normalized_score, 3) : null,
                'interview_score' => $score->interview_normalized_score !== null ? round((float) $score->interview_normalized_score, 3) : null,
                'tie_break_snapshot' => $tieSnapshot,
                '_submitted_at' => $choice->application->submitted_at?->getTimestamp(),
                '_application_no' => (string) $choice->application->application_no,
            ];
        });

        $sorted = $rows->sort(function (array $a, array $b) {
            $primary = $this->compareValues($a['final_weighted_score'], $b['final_weighted_score'], 'DESC');
            if ($primary !== 0) {
                return $primary;
            }

            foreach ($a['tie_break_snapshot'] as $index => $criterion) {
                $cmp = $this->compareValues(
                    $criterion['value'],
                    $b['tie_break_snapshot'][$index]['value'] ?? null,
                    $criterion['direction']
                );
                if ($cmp !== 0) {
                    return $cmp;
                }
            }

            // Deterministic non-policy fallback. It is used only after every configured
            // tie-breaker remains equal/unavailable, preventing unstable database order.
            $submitted = $this->compareValues($a['_submitted_at'], $b['_submitted_at'], 'ASC');
            if ($submitted !== 0) {
                return $submitted;
            }
            $applicationNo = strnatcasecmp($a['_application_no'], $b['_application_no']);
            if ($applicationNo !== 0) {
                return $applicationNo;
            }
            return $a['choice_id'] <=> $b['choice_id'];
        })->values();

        return $sorted->map(function (array $row, int $index) {
            $row['rank'] = $index + 1;
            $row['tie_break_snapshot'][] = [
                'priority' => null,
                'criterion' => 'DETERMINISTIC_FALLBACK',
                'direction' => 'ASC',
                'reference' => 'submitted_at, application_no, choice_id',
                'value' => [
                    'submitted_at' => $row['_choice']->application->submitted_at?->toIso8601String(),
                    'application_no' => $row['_application_no'],
                    'choice_id' => $row['choice_id'],
                ],
                'source' => 'SYSTEM_FALLBACK',
            ];
            unset($row['_submitted_at'], $row['_application_no']);
            return $row;
        });
    }

    private function tieValue(CollegeAdmissionApplicationChoice $choice, string $criterion, ?string $reference): array
    {
        $score = $choice->score;
        return match ($criterion) {
            'QUALIFYING_EXAM_SCORE' => [$score->merit_normalized_score !== null ? (float) $score->merit_normalized_score : null, 'ADMISSION_SCORE.MERIT_NORMALIZED'],
            'ENTRANCE_SCORE' => [$score->entrance_normalized_score !== null ? (float) $score->entrance_normalized_score : null, 'ADMISSION_SCORE.ENTRANCE_NORMALIZED'],
            'INTERVIEW_SCORE' => [$score->interview_normalized_score !== null ? (float) $score->interview_normalized_score : null, 'ADMISSION_SCORE.INTERVIEW_NORMALIZED'],
            'DATE_OF_BIRTH' => [$choice->application->date_of_birth?->format('Y-m-d'), 'APPLICATION.DATE_OF_BIRTH'],
            'APPLICATION_SUBMITTED_AT' => [$choice->application->submitted_at?->getTimestamp(), 'APPLICATION.SUBMITTED_AT'],
            'RELEVANT_SUBJECT_SCORE' => $this->relevantSubjectValue($choice, $reference),
            default => [null, 'UNSUPPORTED'],
        };
    }

    private function relevantSubjectValue(CollegeAdmissionApplicationChoice $choice, ?string $reference): array
    {
        $needle = mb_strtolower(trim((string) $reference));
        if ($needle === '') {
            return [null, 'REFERENCE_EMPTY'];
        }

        foreach ((array) ($choice->score->merit_source_snapshot ?? []) as $source) {
            if (mb_strtolower(trim((string) ($source['label'] ?? ''))) === $needle && isset($source['normalized_score']) && is_numeric($source['normalized_score'])) {
                return [(float) $source['normalized_score'], 'MERIT_SOURCE.NORMALIZED'];
            }
        }

        foreach ($choice->application->fieldValues as $fieldValue) {
            $field = $fieldValue->field;
            if (! $field) {
                continue;
            }
            $fieldKey = mb_strtolower(trim((string) $field->field_key));
            $label = mb_strtolower(trim((string) $field->label));
            if (($fieldKey === $needle || $label === $needle) && is_numeric($fieldValue->value_text)) {
                return [(float) $fieldValue->value_text, 'ADMISSION_FORM.NUMERIC_FIELD'];
            }
        }

        return [null, 'REFERENCE_NOT_RESOLVED'];
    }

    private function compareValues(mixed $a, mixed $b, string $direction): int
    {
        if ($a === null && $b === null) {
            return 0;
        }
        // Available policy data always sorts before missing data. Direction applies
        // only when both candidates have a value.
        if ($a === null) {
            return 1;
        }
        if ($b === null) {
            return -1;
        }

        if (is_numeric($a) && is_numeric($b)) {
            $cmp = (float) $a <=> (float) $b;
        } else {
            $cmp = strcmp((string) $a, (string) $b);
        }

        return $direction === 'DESC' ? -$cmp : $cmp;
    }


    private function publicRankedRow(array $row): array
    {
        unset($row['_choice']);
        return $row;
    }

    private function generatedRow(CollegeAdmissionMeritEntry $entry): array
    {
        return [
            'rank' => $entry->rank,
            'choice_id' => $entry->college_admission_application_choice_id,
            'application_id' => $entry->college_admission_application_id,
            'application_no' => $entry->application?->application_no,
            'candidate_name' => $entry->application?->candidate_name,
            'discipline_name' => $entry->application?->academicPreference?->discipline?->name,
            'discipline_code' => $entry->application?->academicPreference?->discipline?->code,
            'specialization_name' => $entry->application?->academicPreference?->specialization?->name,
            'specialization_code' => $entry->application?->academicPreference?->specialization?->code,
            'final_weighted_score' => (float) $entry->final_weighted_score,
            'merit_score' => $entry->score?->merit_normalized_score !== null ? (float) $entry->score->merit_normalized_score : null,
            'entrance_score' => $entry->score?->entrance_normalized_score !== null ? (float) $entry->score->entrance_normalized_score : null,
            'interview_score' => $entry->score?->interview_normalized_score !== null ? (float) $entry->score->interview_normalized_score : null,
            'tie_break_snapshot' => $entry->tie_break_snapshot ?? [],
        ];
    }

    private function assertRuleOwnedByCollege(College $college, CollegeAdmissionSelectionRule $rule): void
    {
        $rule->loadMissing('intake.offering');
        abort_unless((int) $rule->intake?->offering?->college_id === (int) $college->id, 404);
    }
}
