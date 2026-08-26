<?php

namespace App\Services;

use App\Models\College;
use App\Models\CollegeAdmissionApplicationChoice;
use App\Models\CollegeAdmissionScore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CollegeAdmissionScoreService
{
    public function upsert(College $college, CollegeAdmissionApplicationChoice $choice, array $data, int $actorId, ?string $ip): CollegeAdmissionScore
    {
        $choice->load(['application','selectionRule']);
        $application = $choice->application;
        $rule = $choice->selectionRule;

        if (! $application || (int)$application->college_id !== (int)$college->id) abort(404);
        if ($application->status !== 'SUBMITTED') throw ValidationException::withMessages(['score'=>'Scores can be captured only for a SUBMITTED application.']);
        if ($choice->eligibility_status !== 'ELIGIBLE') throw ValidationException::withMessages(['score'=>'Scores can be captured only for an ELIGIBLE program choice.']);
        if (! $rule) throw ValidationException::withMessages(['score'=>'The submitted choice does not have a locked Selection Rule version.']);

        $this->assertNoDownstream($choice);

        $meritRequired = (float)$rule->merit_weight_percent > 0;
        $entranceRequired = (float)$rule->entrance_weight_percent > 0;
        $interviewRequired = (float)$rule->interview_weight_percent > 0;

        [$meritRaw,$meritMax,$meritNorm] = $this->component('Merit / qualifying', $meritRequired, $data['merit_raw_score'] ?? null, $data['merit_max_score'] ?? null);
        [$entranceRaw,$entranceMax,$entranceNorm] = $this->component('Entrance', $entranceRequired, $data['entrance_raw_score'] ?? null, $data['entrance_max_score'] ?? null);

        $existing = CollegeAdmissionScore::where('college_admission_application_choice_id', $choice->id)->first();
        $interviewNorm = $existing?->interview_normalized_score;

        [$qualificationStatus,$reason,$final] = $this->evaluate($rule, $meritNorm, $entranceNorm, $interviewNorm, $interviewRequired);

        return DB::transaction(function () use ($application,$choice,$rule,$data,$actorId,$ip,$meritRaw,$meritMax,$meritNorm,$entranceRaw,$entranceMax,$entranceNorm,$interviewNorm,$qualificationStatus,$reason,$final,$existing) {
            $before = $existing?->toArray();
            $score = CollegeAdmissionScore::updateOrCreate(
                ['college_admission_application_choice_id'=>$choice->id],
                [
                    'college_admission_application_id'=>$application->id,
                    'college_admission_selection_rule_id'=>$rule->id,
                    'merit_raw_score'=>$meritRaw,'merit_max_score'=>$meritMax,'merit_normalized_score'=>$meritNorm,
                    'entrance_raw_score'=>$entranceRaw,'entrance_max_score'=>$entranceMax,'entrance_normalized_score'=>$entranceNorm,
                    'interview_normalized_score'=>$interviewNorm,'final_weighted_score'=>$final,
                    'qualification_status'=>$qualificationStatus,'qualification_reason'=>$reason,
                    'notes'=>filled($data['notes'] ?? null) ? trim((string)$data['notes']) : null,
                    'scored_at'=>now(),'scored_by'=>$actorId,'updated_by'=>$actorId,
                ]
            );
            $this->audit($score, (int) $application->college_id, $actorId, $ip, $before, $score->fresh()->toArray());
            return $score->fresh(['selectionRule']);
        });
    }

    private function component(string $label, bool $required, mixed $raw, mixed $max): array
    {
        if (! $required) return [null,null,null];
        if (($raw === null || $raw === '') || ($max === null || $max === '')) {
            throw ValidationException::withMessages(['score'=>"{$label} raw score and maximum score are required together."]);
        }
        $raw=(float)$raw; $max=(float)$max;
        if ($max <= 0) throw ValidationException::withMessages(['score'=>"{$label} maximum score must be greater than zero."]);
        if ($raw < 0 || $raw > $max) throw ValidationException::withMessages(['score'=>"{$label} raw score must be between 0 and its maximum score."]);
        return [$raw,$max,round(($raw/$max)*100,3)];
    }

    private function evaluate($rule, ?float $merit, ?float $entrance, ?float $interview, bool $interviewRequired): array
    {
        $fail=[];
        if ((float)$rule->merit_weight_percent > 0 && $rule->minimum_merit_score !== null && $merit !== null && $merit < (float)$rule->minimum_merit_score) $fail[]='Merit score is below the configured minimum.';
        if ((float)$rule->entrance_weight_percent > 0 && $rule->minimum_entrance_score !== null && $entrance !== null && $entrance < (float)$rule->minimum_entrance_score) $fail[]='Entrance score is below the configured minimum.';
        if ($fail) return ['NOT_QUALIFIED', implode(' ', $fail), null];

        if ($interviewRequired && $interview === null) return ['PENDING_INTERVIEW', 'Interview is required by the locked Selection Rule and will be supplied by Interview Scheduling / Evaluation.', null];
        if ($interviewRequired && $rule->minimum_interview_score !== null && $interview < (float)$rule->minimum_interview_score) return ['NOT_QUALIFIED', 'Interview score is below the configured minimum.', null];

        $final = round((($merit ?? 0)*(float)$rule->merit_weight_percent + ($entrance ?? 0)*(float)$rule->entrance_weight_percent + ($interview ?? 0)*(float)$rule->interview_weight_percent)/100,3);
        if ($rule->minimum_final_score !== null && $final < (float)$rule->minimum_final_score) return ['NOT_QUALIFIED','Final weighted score is below the configured minimum.',$final];
        return ['QUALIFIED', null, $final];
    }

    private function assertNoDownstream(CollegeAdmissionApplicationChoice $choice): void
    {
        foreach ([['college_admission_merit_entries','college_admission_application_choice_id'],['college_admission_seat_allocations','college_admission_application_choice_id'],['admissions','college_admission_application_choice_id']] as [$table,$column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table,$column) && DB::table($table)->where($column,$choice->id)->exists()) {
                throw ValidationException::withMessages(['score'=>'Scores are already consumed by downstream Merit / Seat / Admission processing and cannot be changed here.']);
            }
        }
    }

    private function audit(CollegeAdmissionScore $score, int $collegeId, int $actorId, ?string $ip, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id'=>$actorId,'event'=>$before ? 'COLLEGE_ADMISSION_SCORE_UPDATED' : 'COLLEGE_ADMISSION_SCORE_CAPTURED',
            'resource_type'=>'CollegeAdmissionScore','resource_id'=>$score->id,'scope_type'=>'COLLEGE','scope_reference'=>'college:'.$collegeId,
            'before'=>$before ? json_encode($before) : null,'after'=>$after ? json_encode($after) : null,'ip_address'=>$ip,'created_at'=>now(),
        ]);
    }
}
