<?php

namespace App\Services;

use App\Models\College;
use App\Models\CollegeAdmissionSelectionRule;
use App\Models\CollegeAdmissionFormField;
use App\Models\CollegeAdmissionFormMapping;
use App\Models\CollegeProgramIntake;
use App\Models\CollegeProgramReservationPlan;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CollegeAdmissionSelectionRuleService
{
    public function __construct(private CollegeReservationService $reservationService)
    {
    }

    public function create(College $college, array $data, int $actorId, ?string $ip): CollegeAdmissionSelectionRule
    {
        $this->assertCollegeActive($college);
        [$intake, $bucket, $plan] = $this->resolveSeatBucket(
            $college,
            (int) $data['college_program_intake_id'],
            (string) $data['bucket_key']
        );
        $this->validateWeights($data);
        $this->validateThresholds($data);
        $this->validateMeritSources($college, $intake, $data);
        $this->validateTieBreakers($data['tie_breakers'] ?? []);

        return DB::transaction(function () use ($college, $intake, $bucket, $plan, $data, $actorId, $ip) {
            $version = (int) CollegeAdmissionSelectionRule::query()
                ->where('college_program_intake_id', $intake->id)
                ->where('bucket_key', $bucket['bucket_key'])
                ->max('version_no') + 1;

            $rule = CollegeAdmissionSelectionRule::create([
                'college_program_intake_id' => $intake->id,
                'college_program_reservation_plan_id' => $plan?->id,
                'bucket_type' => $bucket['bucket_type'],
                'bucket_key' => $bucket['bucket_key'],
                'basis_capacity' => (int) $bucket['basis_capacity'],
                'version_no' => max(1, $version),
                ...$this->mainRuleData($data),
                'minimum_qualifying_score' => null,
                'status' => 'INACTIVE',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->replaceMeritSources($rule, $data['merit_sources'] ?? []);
            $this->replaceTieBreakers($rule, $data['tie_breakers'] ?? []);
            $rule->load(['meritSources.obtainedField','meritSources.maximumField','tieBreakers']);

            $this->audit('COLLEGE_ADMISSION_SELECTION_RULE_CREATED', $rule, $college, $actorId, $ip, null, $rule->toArray());
            return $rule;
        });
    }

    public function update(CollegeAdmissionSelectionRule $rule, College $college, array $data, int $actorId, ?string $ip): CollegeAdmissionSelectionRule
    {
        $this->assertOwned($rule, $college);
        $this->assertCollegeActive($college);
        if ($rule->status !== 'INACTIVE') {
            throw ValidationException::withMessages(['rule' => 'Only an INACTIVE rule version can be edited. Create a new version instead of changing an active/retired rule.']);
        }
        $this->validateWeights($data);
        $this->validateThresholds($data);
        $intake = $rule->intake()->with('offering')->firstOrFail();
        $this->validateMeritSources($college, $intake, $data);
        $this->validateTieBreakers($data['tie_breakers'] ?? []);

        return DB::transaction(function () use ($rule, $college, $data, $actorId, $ip) {
            $before = $rule->load(['meritSources.obtainedField','meritSources.maximumField','tieBreakers'])->toArray();
            $rule->fill($this->mainRuleData($data));
            $rule->minimum_qualifying_score = null;
            $rule->updated_by = $actorId;
            $rule->save();

            $this->replaceMeritSources($rule, $data['merit_sources'] ?? []);
            $this->replaceTieBreakers($rule, $data['tie_breakers'] ?? []);
            $rule->load(['meritSources.obtainedField','meritSources.maximumField','tieBreakers']);

            $this->audit('COLLEGE_ADMISSION_SELECTION_RULE_UPDATED', $rule, $college, $actorId, $ip, $before, $rule->toArray());
            return $rule;
        });
    }

    public function activate(CollegeAdmissionSelectionRule $rule, College $college, int $actorId, ?string $ip): CollegeAdmissionSelectionRule
    {
        $this->assertOwned($rule, $college);
        $this->assertCollegeActive($college);
        if ($rule->status !== 'INACTIVE') {
            throw ValidationException::withMessages(['rule' => 'Only an INACTIVE rule version can be activated.']);
        }

        [$intake, $bucket, $plan] = $this->resolveSeatBucket($college, $rule->college_program_intake_id, $rule->bucket_key);
        if ((int) $bucket['basis_capacity'] !== (int) $rule->basis_capacity) {
            throw ValidationException::withMessages([
                'rule' => 'The Intake seat capacity for this bucket changed after the rule was created. Create a new Selection Rule version against the current seat bucket.',
            ]);
        }

        $rule->load(['meritSources','tieBreakers']);
        $this->validateWeights($rule->toArray());
        $this->validateThresholds($rule->toArray());
        $activationData = $rule->toArray();
        $activationData['merit_sources'] = $rule->meritSources->toArray();
        $this->validateMeritSources($college, $intake, $activationData);
        $this->validateTieBreakers($rule->tieBreakers->toArray(), true);

        return DB::transaction(function () use ($rule, $college, $plan, $actorId, $ip) {
            $existing = CollegeAdmissionSelectionRule::query()
                ->where('college_program_intake_id', $rule->college_program_intake_id)
                ->where('bucket_key', $rule->bucket_key)
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->get();

            foreach ($existing as $old) {
                $before = $old->toArray();
                $old->update(['status' => 'RETIRED', 'updated_by' => $actorId]);
                $this->audit('COLLEGE_ADMISSION_SELECTION_RULE_RETIRED', $old, $college, $actorId, $ip, $before, $old->fresh()->toArray());
            }

            $before = $rule->toArray();
            $rule->update([
                'college_program_reservation_plan_id' => $plan?->id,
                'status' => 'ACTIVE',
                'updated_by' => $actorId,
            ]);
            $rule->load('tieBreakers');
            $this->audit('COLLEGE_ADMISSION_SELECTION_RULE_ACTIVATED', $rule, $college, $actorId, $ip, $before, $rule->toArray());
            return $rule;
        });
    }

    public function retire(CollegeAdmissionSelectionRule $rule, College $college, int $actorId, ?string $ip): CollegeAdmissionSelectionRule
    {
        $this->assertOwned($rule, $college);
        $this->assertCollegeActive($college);
        if ($rule->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['rule' => 'Only an ACTIVE rule can be retired.']);
        }
        $before = $rule->load('tieBreakers')->toArray();
        $rule->update(['status' => 'RETIRED', 'updated_by' => $actorId]);
        $this->audit('COLLEGE_ADMISSION_SELECTION_RULE_RETIRED', $rule, $college, $actorId, $ip, $before, $rule->fresh()->load('tieBreakers')->toArray());
        return $rule;
    }

    /**
     * Reservation is optional per effective Intake seat bucket.
     * - no plan => full bucket is Open/General and Selection may proceed;
     * - plan exists + INACTIVE => Selection is blocked for that bucket;
     * - plan exists + ACTIVE => Selection consumes that Reservation context.
     */
    private function resolveSeatBucket(College $college, int $intakeId, string $bucketKey): array
    {
        $intake = CollegeProgramIntake::query()
            ->with('offering')
            ->whereKey($intakeId)
            ->where('status', 'ACTIVE')
            ->whereHas('offering', fn ($q) => $q->where('college_id', $college->id)->where('status', 'ACTIVE'))
            ->first();

        if (! $intake) {
            throw ValidationException::withMessages([
                'college_program_intake_id' => 'Selection requires an ACTIVE Program Offering and ACTIVE Intake / Seat Capacity for this College.',
            ]);
        }

        $bucket = $this->reservationService->availableBuckets($intake)->firstWhere('bucket_key', $bucketKey);
        if (! $bucket) {
            throw ValidationException::withMessages([
                'bucket_key' => 'The selected admission seat bucket no longer exists in the active Intake.',
            ]);
        }

        $plan = CollegeProgramReservationPlan::query()
            ->where('college_program_intake_id', $intake->id)
            ->where('bucket_key', $bucketKey)
            ->first();

        if ($plan && $plan->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'bucket_key' => 'Reservation / Seat Distribution is defined for this seat bucket but is INACTIVE. Activate that Reservation plan before configuring or activating Selection Rules for this bucket.',
            ]);
        }

        return [$intake, $bucket, $plan];
    }

    private function mainRuleData(array $data): array
    {
        $mode = $data['selection_mode'];

        return [
            'name' => $data['name'],
            'code' => $data['code'],
            'selection_mode' => $mode,
            'merit_weight_percent' => $data['merit_weight_percent'],
            'entrance_weight_percent' => $data['entrance_weight_percent'],
            'interview_weight_percent' => $data['interview_weight_percent'],
            'minimum_merit_score' => in_array($mode, ['MERIT', 'COMBINED'], true) ? ($data['minimum_merit_score'] ?? null) : null,
            'minimum_entrance_score' => in_array($mode, ['ENTRANCE', 'COMBINED'], true) ? ($data['minimum_entrance_score'] ?? null) : null,
            'minimum_interview_score' => in_array($mode, ['INTERVIEW', 'COMBINED'], true) ? ($data['minimum_interview_score'] ?? null) : null,
            'minimum_final_score' => $mode === 'COMBINED' ? ($data['minimum_final_score'] ?? null) : null,
            'roster_rule_reference' => $data['roster_rule_reference'] ?? null,
            // Kept as human-readable policy wording. Executable tie-breakers are child rows.
            'tie_breaker_rules' => $data['tie_breaker_rules'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function replaceMeritSources(CollegeAdmissionSelectionRule $rule, array $sources): void
    {
        $rule->meritSources()->delete();
        foreach (array_values($sources) as $index => $source) {
            $rule->meritSources()->create([
                'label' => trim((string) $source['label']),
                'source_type' => 'FORM_FIELD_PAIR',
                'obtained_field_id' => (int) $source['obtained_field_id'],
                'maximum_field_id' => (int) $source['maximum_field_id'],
                'weight_percent' => (float) $source['weight_percent'],
                'display_order' => ($index + 1) * 10,
            ]);
        }
    }

    private function validateMeritSources(College $college, CollegeProgramIntake $intake, array $data): void
    {
        $sources = array_values($data['merit_sources'] ?? []);
        $meritWeight = (float) ($data['merit_weight_percent'] ?? 0);
        if ($meritWeight <= 0 && $sources !== []) {
            throw ValidationException::withMessages(['merit_sources' => 'Merit source mapping can be configured only when Merit has a positive Selection weight.']);
        }
        if ($sources === []) return; // backwards-compatible manual Score Capture.

        $total = array_sum(array_map(fn ($item) => (float) ($item['weight_percent'] ?? 0), $sources));
        if (abs($total - 100) > 0.001) {
            throw ValidationException::withMessages(['merit_sources' => 'Mapped Merit source weights must total exactly 100%.']);
        }

        $offeringId = (int) $intake->college_program_offering_id;
        $templateIds = CollegeAdmissionFormMapping::query()
            ->where('college_id', $college->id)
            ->where('college_program_offering_id', $offeringId)
            ->where('status', 'ACTIVE')
            ->pluck('college_admission_form_template_id');
        if ($templateIds->isEmpty()) {
            throw ValidationException::withMessages(['merit_sources' => 'No ACTIVE Admission Form is mapped to this Program Offering, so dynamic Merit fields cannot be linked.']);
        }

        $fieldIds = collect($sources)->flatMap(fn ($item) => [(int)$item['obtained_field_id'], (int)$item['maximum_field_id']])->unique()->values();
        $valid = CollegeAdmissionFormField::query()
            ->whereIn('id', $fieldIds)
            ->where('field_type', 'NUMBER')
            ->where('status', 'ACTIVE')
            ->whereHas('step', fn ($q) => $q->whereIn('college_admission_form_template_id', $templateIds))
            ->pluck('id')->map(fn ($id)=>(int)$id)->all();
        $validSet = array_fill_keys($valid, true);
        foreach ($sources as $index => $source) {
            $obtained = (int)$source['obtained_field_id'];
            $maximum = (int)$source['maximum_field_id'];
            if (!isset($validSet[$obtained]) || !isset($validSet[$maximum])) {
                throw ValidationException::withMessages(["merit_sources.$index.obtained_field_id" => 'Both Merit source fields must be ACTIVE NUMBER fields from the Admission Form mapped to this Program Offering.']);
            }
            if ($obtained === $maximum) {
                throw ValidationException::withMessages(["merit_sources.$index.maximum_field_id" => 'Obtained and Maximum fields must be different.']);
            }
        }
    }

    private function replaceTieBreakers(CollegeAdmissionSelectionRule $rule, array $tieBreakers): void
    {
        $rule->tieBreakers()->delete();

        foreach (array_values($tieBreakers) as $index => $item) {
            $rule->tieBreakers()->create([
                'priority' => $index + 1,
                'criterion' => $item['criterion'],
                'comparison_direction' => $item['comparison_direction'],
                'criterion_reference' => filled($item['criterion_reference'] ?? null)
                    ? trim((string) $item['criterion_reference'])
                    : null,
            ]);
        }
    }

    private function validateWeights(array $data): void
    {
        $mode = $data['selection_mode'];
        $merit = (float) $data['merit_weight_percent'];
        $entrance = (float) $data['entrance_weight_percent'];
        $interview = (float) $data['interview_weight_percent'];
        $total = $merit + $entrance + $interview;
        $positiveComponents = collect([$merit, $entrance, $interview])->filter(fn ($weight) => $weight > 0)->count();

        $valid = match ($mode) {
            'MERIT' => abs($merit - 100) < 0.001 && abs($entrance) < 0.001 && abs($interview) < 0.001,
            'ENTRANCE' => abs($entrance - 100) < 0.001 && abs($merit) < 0.001 && abs($interview) < 0.001,
            'INTERVIEW' => abs($interview - 100) < 0.001 && abs($merit) < 0.001 && abs($entrance) < 0.001,
            'COMBINED' => abs($total - 100) < 0.001 && $positiveComponents >= 2,
            default => false,
        };

        if (! $valid) {
            throw ValidationException::withMessages([
                'weights' => 'MERIT, ENTRANCE or INTERVIEW-only modes require that component at 100%. COMBINED requires at least two positive Merit / Entrance / Interview weights totaling exactly 100%.',
            ]);
        }
    }

    private function validateThresholds(array $data): void
    {
        $mode = $data['selection_mode'];
        $merit = $data['minimum_merit_score'] ?? null;
        $entrance = $data['minimum_entrance_score'] ?? null;
        $interview = $data['minimum_interview_score'] ?? null;
        $final = $data['minimum_final_score'] ?? null;

        foreach ([
            'minimum_merit_score' => $merit,
            'minimum_entrance_score' => $entrance,
            'minimum_interview_score' => $interview,
            'minimum_final_score' => $final,
        ] as $field => $value) {
            if ($value !== null && $value !== '' && ((float) $value < 0 || (float) $value > 100)) {
                throw ValidationException::withMessages([$field => 'Selection thresholds use a normalized 0-100 score.']);
            }
        }

        $weights = [
            'minimum_merit_score' => (float) ($data['merit_weight_percent'] ?? 0),
            'minimum_entrance_score' => (float) ($data['entrance_weight_percent'] ?? 0),
            'minimum_interview_score' => (float) ($data['interview_weight_percent'] ?? 0),
        ];
        $values = [
            'minimum_merit_score' => $merit,
            'minimum_entrance_score' => $entrance,
            'minimum_interview_score' => $interview,
        ];

        foreach ($weights as $field => $weight) {
            if ($weight <= 0 && filled($values[$field])) {
                throw ValidationException::withMessages([$field => 'A minimum score can be set only for a scoring component that has a positive weight.']);
            }
        }

        if ($mode !== 'COMBINED' && filled($final)) {
            throw ValidationException::withMessages(['minimum_final_score' => 'Final weighted minimum is applicable only to a Combined rule.']);
        }
    }

    private function validateTieBreakers(array $tieBreakers, bool $activation = false): void
    {
        if ($activation && $tieBreakers === []) {
            throw ValidationException::withMessages([
                'tie_breakers' => 'Add at least one structured tie-breaker before activating this Selection Rule so future merit ranking is deterministic.',
            ]);
        }

        if (count($tieBreakers) > 10) {
            throw ValidationException::withMessages(['tie_breakers' => 'A maximum of 10 tie-breakers is allowed.']);
        }

        $seen = [];
        foreach ($tieBreakers as $item) {
            $criterion = (string) ($item['criterion'] ?? '');
            $reference = trim((string) ($item['criterion_reference'] ?? ''));

            if ($criterion === 'RELEVANT_SUBJECT_SCORE' && $reference === '') {
                throw ValidationException::withMessages([
                    'tie_breakers' => 'Relevant Subject Score requires a subject/field reference such as English, Mathematics or Physics.',
                ]);
            }

            $identity = $criterion.'|'.mb_strtolower($reference);
            if (isset($seen[$identity])) {
                throw ValidationException::withMessages([
                    'tie_breakers' => 'The same tie-break criterion/reference cannot be added twice in one Selection Rule.',
                ]);
            }
            $seen[$identity] = true;
        }
    }

    private function assertOwned(CollegeAdmissionSelectionRule $rule, College $college): void
    {
        $intake = $rule->intake()->with('offering')->first();
        abort_unless($intake && $intake->offering?->college_id === $college->id, 404);
    }

    private function assertCollegeActive(College $college): void
    {
        if ($college->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['college' => 'This College is inactive.']);
        }
    }

    private function audit(string $event, CollegeAdmissionSelectionRule $rule, College $college, int $actorId, ?string $ip, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId,
            'event' => $event,
            'resource_type' => 'CollegeAdmissionSelectionRule',
            'resource_id' => $rule->id,
            'scope_type' => 'COLLEGE',
            'scope_reference' => 'college:'.$college->id,
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
