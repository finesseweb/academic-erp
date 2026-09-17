<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\College;
use App\Models\CollegeAdmissionApplicationFieldValue;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentProfileValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentEnrollmentService
{
    public function __construct(
        private readonly FeeClearanceService $feeClearance,
        private readonly ApplicantStudentPromotionService $promotion,
    ) {}

    public function enroll(College $college, Admission $admission, int $actorId, ?string $ip): StudentEnrollment
    {
        return DB::transaction(function () use ($college, $admission, $actorId, $ip) {
            $admission = Admission::query()->whereKey($admission->id)->lockForUpdate()->firstOrFail();
            if ((int) $admission->college_id !== (int) $college->id || $admission->status !== 'CONFIRMED') {
                throw ValidationException::withMessages(['enrollment' => 'Only a confirmed admission in this College can be enrolled.']);
            }

            $admission->loadMissing(['application.fieldValues.field', 'application.academicPreference', 'application.courseChoices', 'intake.offering']);
            $application = $admission->application;
            $offering = $admission->intake?->offering;
            if (! $application || ! $offering || (int) $offering->college_id !== (int) $college->id) {
                throw ValidationException::withMessages(['enrollment' => 'The admission does not have a valid College Programme Offering.']);
            }

            // Applicant-owned applications must have a valid same-College Applicant Profile
            // before any Student/Enrollment row is created. Staff-created applications have
            // no applicant_user_id and intentionally bypass Applicant Profile promotion.
            $this->promotion->assertCanPromote($application);

            $existing = StudentEnrollment::query()->where('admission_id', $admission->id)->lockForUpdate()->first();
            if ($existing) {
                if ($existing->status === 'ENROLLED') return $existing;
                throw ValidationException::withMessages(['enrollment' => 'This admission already has an enrollment record and cannot be enrolled again.']);
            }

            $clearance = $this->feeClearance->forAdmission($college, $admission, (int) $offering->academic_session_id, (int) $offering->id);
            if (! (bool) ($clearance['is_cleared'] ?? false)) {
                throw ValidationException::withMessages(['enrollment' => 'Required Fee Clearance is pending. Refresh the queue before enrolling.']);
            }

            $student = Student::query()->where('admission_id', $admission->id)->lockForUpdate()->first();
            if (! $student) {
                $student = Student::create([
                    'college_id' => $college->id,
                    'user_id' => $application->applicant_user_id,
                    'admission_id' => $admission->id,
                    'college_admission_application_id' => $application->id,
                    'source_type' => 'ADMISSION',
                    'student_uid' => null, // ENR-3 owns institutional identifier generation.
                    'full_name' => $application->candidate_name,
                    'date_of_birth' => $application->date_of_birth,
                    'email' => $application->email,
                    'phone' => $application->phone,
                    'status' => 'ACTIVE',
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
                $this->copyStudentProfileValues($application->fieldValues, $student);
            }

            $enrollment = StudentEnrollment::create([
                'student_id' => $student->id,
                'college_id' => $college->id,
                'college_program_offering_id' => $offering->id,
                'curriculum_id' => $application->academicPreference?->curriculum_id,
                'discipline_id' => $application->academicPreference?->discipline_id,
                'specialization_id' => $application->academicPreference?->specialization_id,
                'admission_id' => $admission->id,
                'source_type' => 'ADMISSION',
                'status' => 'ENROLLED',
                'enrolled_at' => now(),
                'enrolled_by' => $actorId,
            ]);

            foreach ($application->courseChoices as $course) {
                DB::table('student_enrollment_course_choices')->insert([
                    'student_enrollment_id' => $enrollment->id,
                    'curriculum_term_id' => $course->curriculum_term_id,
                    'curriculum_slot_id' => $course->curriculum_slot_id,
                    'curriculum_course_mapping_id' => $course->curriculum_course_mapping_id,
                    'course_id' => $course->course_id,
                    'selection_source' => $course->selection_source,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            // ENR-3.4: enrollment deliberately leaves institutional identity pending.
            // Authorized College staff assign Student UID / University Roll / Class Roll
            // from Student Management -> Student Identity after numbering rules are final.

            if ($application->applicant_user_id) {
                $this->promotion->enableStudentAccess($application, $student->id);
            }

            DB::table('audit_logs')->insert([
                'actor_user_id' => $actorId,
                'event' => 'student.enrolled',
                'resource_type' => 'student_enrollment',
                'resource_id' => $enrollment->id,
                'scope_type' => 'COLLEGE',
                'scope_reference' => 'college:'.$college->id,
                'before' => null,
                'after' => json_encode([
                    'student_id' => $student->id,
                    'admission_id' => $admission->id,
                    'college_program_offering_id' => $offering->id,
                    'discipline_id' => $application->academicPreference?->discipline_id,
                    'source_type' => 'ADMISSION',
                    'status' => 'ENROLLED',
                ]),
                'ip_address' => $ip,
                'created_at' => now(),
            ]);

            return $enrollment->fresh();
        });
    }

    private function copyStudentProfileValues($values, Student $student): void
    {
        foreach ($values as $value) {
            $field = $value->field;
            if (! $field || $field->student_data_policy !== 'STUDENT_PROFILE' || ! $field->student_profile_key) continue;
            StudentProfileValue::updateOrCreate(
                ['student_id' => $student->id, 'profile_key' => $field->student_profile_key],
                [
                    'source_application_field_id' => $field->id,
                    'label_snapshot' => $field->label,
                    'value_text' => $value->value_text,
                    'value_json' => $value->value_json,
                    'file_path' => $value->file_path,
                    'file_name' => $value->file_name,
                    'file_mime' => $value->file_mime,
                    'file_size' => $value->file_size,
                ]
            );
        }
    }
}
