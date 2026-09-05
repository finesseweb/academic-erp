<?php

namespace App\Services;

use App\Models\AcademicPolicy;
use App\Models\CollegeProgramOffering;
use Illuminate\Validation\ValidationException;

class AcademicPolicyResolverService
{
    public function resolveForOffering(CollegeProgramOffering $offering): ?AcademicPolicy
    {
        $offering->loadMissing(['college:id,university_id', 'programTemplate.degree.degreeLevel:id', 'curriculum:id']);

        $universityId = (int) $offering->college?->university_id;
        if (! $universityId) {
            return null;
        }

        $degreeLevelId = $offering->programTemplate?->degree?->degreeLevel?->id;
        $today = now()->toDateString();

        $candidates = AcademicPolicy::query()
            ->where('university_id', $universityId)
            ->where('academic_session_id', $offering->academic_session_id)
            ->where('lifecycle_status', 'ACTIVE')
            ->where('approval_status', 'APPROVED')
            ->where('is_current_version', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $today);
            })
            ->get();

        $ranked = $candidates->map(function (AcademicPolicy $policy) use ($offering, $degreeLevelId) {
            $rank = match ($policy->scope_type) {
                'CURRICULUM' => (int) $policy->curriculum_id === (int) $offering->curriculum_id ? 400 : 0,
                'PROGRAM_TEMPLATE' => (int) $policy->program_template_id === (int) $offering->program_template_id ? 300 : 0,
                'DEGREE_LEVEL' => $degreeLevelId && (int) $policy->degree_level_id === (int) $degreeLevelId ? 200 : 0,
                'UNIVERSITY' => 100,
                default => 0,
            };

            return ['policy' => $policy, 'rank' => $rank];
        })->filter(fn ($row) => $row['rank'] > 0);

        if ($ranked->isEmpty()) {
            return null;
        }

        $bestRank = (int) $ranked->max('rank');
        $best = $ranked->where('rank', $bestRank)->values();

        if ($best->count() > 1) {
            throw ValidationException::withMessages([
                'academic_policy' => 'More than one current ACTIVE + APPROVED Academic Policy matches this Program Offering at the same scope. Resolve the policy conflict before academic progression or later-period fee demand processing.',
            ]);
        }

        return $best->first()['policy'];
    }
}
