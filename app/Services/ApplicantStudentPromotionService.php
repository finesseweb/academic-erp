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

    /**
     * Assert that an applicant-owned application can reuse its existing login
     * during Student enrollment. Staff-created applications have no applicant
     * user and must not manufacture an ApplicantProfile.
     */
    public function assertCanPromote(CollegeAdmissionApplication $application): void
    {
        if (! $application->applicant_user_id) {
            return;
        }

        if ($application->status !== 'SUBMITTED') {
            throw ValidationException::withMessages([
                'application' => 'Only a submitted applicant-owned application can be promoted to Student access.',
            ]);
        }

        $profile = ApplicantProfile::query()
            ->where('user_id', $application->applicant_user_id)
            ->first();

        if (! $profile) {
            throw ValidationException::withMessages([
                'application' => 'The applicant-owned application has no Applicant Profile. Enrollment was not created.',
            ]);
        }

        if ((int) $profile->college_id !== (int) $application->college_id) {
            throw ValidationException::withMessages([
                'application' => 'The Applicant Profile belongs to a different College. Enrollment was not created.',
            ]);
        }
    }

    /** Called by final Admission Approval/Enrollment once a real Student master row exists. */
    public function enableStudentAccess(
        CollegeAdmissionApplication $application,
        int $studentId
    ): ApplicantProfile {
        $this->assertCanPromote($application);

        if (! $application->applicant_user_id) {
            throw ValidationException::withMessages([
                'application' => 'This application has no Applicant login to promote.',
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
            $profile = ApplicantProfile::query()
                ->where('user_id', $application->applicant_user_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $profile->college_id !== (int) $application->college_id) {
                throw ValidationException::withMessages([
                    'application' => 'The Applicant Profile belongs to a different College. Enrollment was not created.',
                ]);
            }

            if ($profile->student_id && (int) $profile->student_id !== $studentId) {
                throw ValidationException::withMessages([
                    'application' => 'This Applicant Profile is already linked to a different Student record.',
                ]);
            }

            $profile->update([
                'lifecycle_status' => 'STUDENT_ENABLED',
                'student_id' => $studentId,
                'student_enabled_at' => $profile->student_enabled_at ?: now(),
            ]);

            $profile->user()->update([
                'account_type' => 'STUDENT',
                'status' => 'ACTIVE',
            ]);

            return $profile->fresh();
        });
    }
}
