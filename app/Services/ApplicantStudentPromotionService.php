<?php

namespace App\Services;

use App\Models\ApplicantProfile;
use App\Models\CollegeAdmissionApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicantStudentPromotionService
{
    public function __construct(
        private EffectiveCurriculumScopeService $effectiveScope,
    ) {
    }

    /** Called by final Admission Approval/Enrollment once a real Student master row exists. */
    public function enableStudentAccess(
        CollegeAdmissionApplication $application,
        int $studentId
    ): ApplicantProfile {
        if (! $application->applicant_user_id || $application->status !== 'SUBMITTED') {
            throw ValidationException::withMessages([
                'application' =>
                    'Only a submitted applicant-owned application can be promoted to Student access.',
            ]);
        }

        $application->loadMissing([
            'academicPreference.offering.curriculum',
        ]);

        $preference = $application->academicPreference;
        if ($preference?->offering) {
            $this->effectiveScope->assertPreference(
                $preference->offering,
                $preference->discipline_id ? (int) $preference->discipline_id : null,
                $preference->specialization_id ? (int) $preference->specialization_id : null
            );
        }

        return DB::transaction(function () use ($application, $studentId) {
            $profile = ApplicantProfile::where(
                'user_id',
                $application->applicant_user_id
            )->lockForUpdate()->firstOrFail();

            $profile->update([
                'lifecycle_status' => 'STUDENT_ENABLED',
                'student_id' => $studentId,
                'student_enabled_at' => now(),
            ]);

            $profile->user()->update([
                'account_type' => 'STUDENT',
                'status' => 'ACTIVE',
            ]);

            return $profile->fresh();
        });
    }
}
