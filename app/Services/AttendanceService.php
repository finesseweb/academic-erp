<?php

namespace App\Services;

use App\Models\AcademicPolicy;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRegister;
use App\Models\ClassSchedule;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Models\FacultyAllocation;
use App\Models\StudentEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(private AcademicPolicyResolverService $policies) {}

    /** @return array{allocation: FacultyAllocation, offering: CollegeProgramOffering, policy: AcademicPolicy} */
    public function context(College $college, ClassSchedule $schedule): array
    {
        $schedule->loadMissing(['timetableEntry.facultyAllocation.courseOffering.batch.offering', 'timetableEntry.facultyAllocation.courseOffering.curriculumCourseMapping']);
        $allocation = $schedule->timetableEntry?->facultyAllocation;
        if (! $allocation instanceof FacultyAllocation) {
            abort(404);
        }
        $offering = $allocation->courseOffering?->batch?->offering;
        if (! $offering instanceof CollegeProgramOffering || (int) $offering->college_id !== (int) $college->id) {
            abort(404);
        }
        if ($schedule->status === 'CANCELLED') {
            throw ValidationException::withMessages(['attendance' => 'Attendance cannot be entered for a cancelled class.']);
        }

        $classDate = Carbon::parse($schedule->class_date)->toDateString();
        $policy = $this->policies->resolveForOffering($offering, $classDate);
        $policy?->loadMissing('attendanceRule');
        if (! $policy || ! $policy->attendanceRule) {
            throw ValidationException::withMessages(['academic_policy' => 'No applicable ACTIVE + APPROVED Academic Policy with Attendance Rules is configured for this Programme Offering.']);
        }

        return compact('allocation', 'offering', 'policy');
    }

    /** @return Collection<int, StudentEnrollment> */
    public function roster(College $college, ClassSchedule $schedule): Collection
    {
        ['allocation' => $allocation] = $this->context($college, $schedule);
        $courseOffering = $allocation->courseOffering;

        return StudentEnrollment::query()->with('student:id,full_name,student_uid,university_roll_no')
            ->where('college_id', $college->id)
            ->whereDate('enrolled_at', '<=', Carbon::parse($schedule->class_date)->toDateString())
            ->where(fn ($q) => $q->where('status', 'ENROLLED')->orWhereDate('cancelled_at', '>', Carbon::parse($schedule->class_date)->toDateString()))
            ->where('college_program_offering_id', $courseOffering->batch->college_program_offering_id)
            ->where('batch_id', $courseOffering->batch_id)
            ->when($allocation->section_id, fn ($q) => $q->where('section_id', $allocation->section_id))
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('student_enrollment_course_choices as sec')
                ->whereColumn('sec.student_enrollment_id', 'student_enrollments.id')
                ->where('sec.curriculum_course_mapping_id', $courseOffering->curriculum_course_mapping_id))
            ->orderByRaw('class_roll_no is null')->orderBy('class_roll_no')->orderBy('id')->get();
    }

    /** @param array<string, mixed> $data */
    public function save(College $college, ClassSchedule $schedule, array $data, int $actor): AttendanceRegister
    {
        $context = $this->context($college, $schedule);
        $rosterIds = $this->roster($college, $schedule)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $records = $data['records'] ?? null;
        if (! is_array($records)) {
            throw ValidationException::withMessages(['records' => 'Submit a valid attendance roster.']);
        }
        $submittedIds = array_map(fn (array $row) => (int) $row['student_enrollment_id'], $records);
        sort($rosterIds);
        sort($submittedIds);
        if ($rosterIds !== $submittedIds) {
            throw ValidationException::withMessages(['records' => 'Attendance must include every currently eligible student exactly once. Refresh the roster and try again.']);
        }

        return DB::transaction(function () use ($college, $schedule, $context, $data, $records, $actor) {
            $register = AttendanceRegister::where('class_schedule_id', $schedule->id)->lockForUpdate()->first();
            if ($register && $register->status === 'FINALIZED') {
                throw ValidationException::withMessages(['attendance' => 'Finalized attendance is locked. Use the correction action first.']);
            }
            $register ??= AttendanceRegister::create(['class_schedule_id' => $schedule->id, 'academic_policy_id' => $context['policy']->id, 'status' => 'DRAFT', 'created_by' => $actor, 'updated_by' => $actor]);
            if ((int) $register->academic_policy_id !== (int) $context['policy']->id) {
                throw ValidationException::withMessages(['academic_policy' => 'The applicable Academic Policy changed after this register was created. Resolve the historical register before continuing.']);
            }

            foreach ($records as $row) {
                AttendanceRecord::updateOrCreate(
                    ['attendance_register_id' => $register->id, 'student_enrollment_id' => $row['student_enrollment_id']],
                    ['attendance_status' => $row['attendance_status'], 'remarks' => $row['remarks'] ?? null, 'created_by' => $actor, 'updated_by' => $actor],
                );
            }
            $before = $register->toArray();
            if ($data['action'] === 'FINALIZE') {
                $register->update(['status' => 'FINALIZED', 'finalized_at' => now(), 'finalized_by' => $actor, 'updated_by' => $actor]);
                if ($schedule->status === 'SCHEDULED') {
                    $schedule->update(['status' => 'COMPLETED', 'updated_by' => $actor]);
                }
            } else {
                $register->update(['updated_by' => $actor]);
            }
            $this->audit($data['action'] === 'FINALIZE' ? 'ATTENDANCE_FINALIZED' : 'ATTENDANCE_DRAFT_SAVED', $register, $college, $actor, $before, $register->fresh()->toArray());

            return $register;
        });
    }

    public function reopen(College $college, AttendanceRegister $register, string $reason, int $actor): void
    {
        $register->loadMissing('classSchedule');
        $this->context($college, $register->classSchedule);
        if ($register->status !== 'FINALIZED') {
            throw ValidationException::withMessages(['attendance' => 'Only finalized attendance can be reopened.']);
        }
        DB::transaction(function () use ($register, $reason, $college, $actor) {
            $before = $register->toArray();
            $register->update(['status' => 'DRAFT', 'revision_no' => $register->revision_no + 1, 'correction_reason' => $reason, 'finalized_at' => null, 'finalized_by' => null, 'updated_by' => $actor]);
            $this->audit('ATTENDANCE_REOPENED_FOR_CORRECTION', $register, $college, $actor, $before, $register->fresh()->toArray());
        });
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function audit(string $event, AttendanceRegister $row, College $college, int $actor, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert(['actor_user_id' => $actor, 'event' => $event, 'resource_type' => 'AttendanceRegister', 'resource_id' => $row->id, 'scope_type' => 'COLLEGE', 'scope_reference' => 'college:'.$college->id, 'before' => $before ? json_encode($before) : null, 'after' => $after ? json_encode($after) : null, 'created_at' => now()]);
    }
}
