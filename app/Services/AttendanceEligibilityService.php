<?php

namespace App\Services;

use App\Models\AcademicPolicy;
use App\Models\AttendanceExceptionRequest;
use App\Models\College;
use App\Models\CourseOffering;
use App\Models\StudentAttendanceEligibility;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceEligibilityService
{
    /** @return array{policy: mixed, rule: mixed, held: int, attended: int, percent: float, normal: bool, approved_type: ?string, eligible: bool, basis: string} */
    public function evaluate(College $college, StudentEnrollment $enrollment, CourseOffering $courseOffering): array
    {
        $this->assertScope($college, $enrollment, $courseOffering);
        $courseOffering->loadMissing('batch.offering', 'curriculumCourseMapping.slot');

        $latest = DB::table('attendance_records as ar')
            ->join('attendance_registers as reg', 'reg.id', '=', 'ar.attendance_register_id')
            ->join('class_schedules as cs', 'cs.id', '=', 'reg.class_schedule_id')
            ->join('timetable_entries as te', 'te.id', '=', 'cs.timetable_entry_id')
            ->join('faculty_allocations as fa', 'fa.id', '=', 'te.faculty_allocation_id')
            ->where('reg.status', 'FINALIZED')->where('ar.student_enrollment_id', $enrollment->id)
            ->where('fa.course_offering_id', $courseOffering->id)
            ->orderByDesc('cs.class_date')->select('reg.academic_policy_id')->first();
        if (! $latest) {
            throw ValidationException::withMessages(['attendance' => 'Finalized attendance is required before evaluating eligibility.']);
        }

        $policy = AcademicPolicy::with('attendanceRule')->find($latest->academic_policy_id);
        if (! $policy instanceof AcademicPolicy) {
            throw ValidationException::withMessages(['academic_policy' => 'The Attendance Register policy snapshot no longer exists.']);
        }
        $rule = $policy->attendanceRule;
        if (! $rule) {
            throw ValidationException::withMessages(['academic_policy' => 'The attendance policy snapshot has no Attendance Rule.']);
        }

        $query = DB::table('attendance_records as ar')
            ->join('attendance_registers as reg', 'reg.id', '=', 'ar.attendance_register_id')
            ->join('class_schedules as cs', 'cs.id', '=', 'reg.class_schedule_id')
            ->join('timetable_entries as te', 'te.id', '=', 'cs.timetable_entry_id')
            ->join('faculty_allocations as fa', 'fa.id', '=', 'te.faculty_allocation_id')
            ->join('course_offerings as co', 'co.id', '=', 'fa.course_offering_id')
            ->join('batches as b', 'b.id', '=', 'co.batch_id')
            ->where('reg.status', 'FINALIZED')->where('ar.student_enrollment_id', $enrollment->id);
        if ($rule->calculation_level === 'COURSE') {
            $query->where('co.id', $courseOffering->id);
        } elseif ($rule->calculation_level === 'TERM') {
            $query->join('curriculum_course_mappings as ccm', 'ccm.id', '=', 'co.curriculum_course_mapping_id')
                ->join('curriculum_slots as slot', 'slot.id', '=', 'ccm.curriculum_slot_id')
                ->where('b.college_program_offering_id', $enrollment->college_program_offering_id)
                ->where('slot.curriculum_term_id', $courseOffering->curriculumCourseMapping->slot->curriculum_term_id);
        } else {
            $query->where('b.college_program_offering_id', $enrollment->college_program_offering_id);
        }
        $totals = $query->selectRaw("COUNT(*) held, SUM(CASE WHEN ar.attendance_status IN ('PRESENT','LATE','EXCUSED') THEN 1 ELSE 0 END) attended")->first();
        $held = (int) ($totals->held ?? 0);
        $attended = (int) ($totals->attended ?? 0);
        $raw = $held ? 100 * $attended / $held : 0;
        $percent = match ($rule->rounding_rule) {
            'FLOOR' => floor($raw), 'CEIL' => ceil($raw), 'NEAREST' => round($raw), default => round($raw, 2)
        };
        $normal = $percent >= (float) $rule->minimum_attendance_percent;
        $approvedType = AttendanceExceptionRequest::where('student_enrollment_id', $enrollment->id)->where('course_offering_id', $courseOffering->id)->where('academic_policy_id', $policy->id)->where('status', 'APPROVED')->latest('decided_at')->value('type');
        $eligible = ! $rule->attendance_required_for_exam || $normal || $approvedType !== null;
        $basis = ! $rule->attendance_required_for_exam ? 'NOT_REQUIRED' : ($normal ? 'NORMAL' : ($approvedType ?? 'SHORTAGE'));

        return compact('policy', 'rule', 'held', 'attended', 'percent', 'normal', 'eligible', 'basis') + ['approved_type' => $approvedType];
    }

    /** @param array<string, mixed> $data */
    public function request(College $college, StudentEnrollment $enrollment, CourseOffering $courseOffering, array $data, int $actor): AttendanceExceptionRequest
    {
        $evaluation = $this->evaluate($college, $enrollment, $courseOffering);
        if ($evaluation['normal']) {
            throw ValidationException::withMessages(['attendance' => 'This student already satisfies the normal attendance threshold.']);
        }
        if ($data['type'] === 'CONDONATION') {
            if (! $evaluation['rule']->allow_condonation) {
                throw ValidationException::withMessages(['type' => 'The applicable policy does not allow condonation.']);
            }
            $minimum = (float) $evaluation['rule']->condonation_minimum_percent;
            $shortage = (float) $evaluation['rule']->minimum_attendance_percent - $evaluation['percent'];
            if ($evaluation['percent'] < $minimum || ($evaluation['rule']->maximum_condonable_shortage_percent !== null && $shortage > (float) $evaluation['rule']->maximum_condonable_shortage_percent)) {
                throw ValidationException::withMessages(['attendance' => 'The shortage is outside the policy condonation limits.']);
            }
        } elseif (! $evaluation['rule']->allow_special_exemption) {
            throw ValidationException::withMessages(['type' => 'The applicable policy does not allow medical/special exemption.']);
        }
        if (AttendanceExceptionRequest::where('student_enrollment_id', $enrollment->id)->where('course_offering_id', $courseOffering->id)->where('type', $data['type'])->where('status', 'PENDING')->exists()) {
            throw ValidationException::withMessages(['type' => 'A pending request of this type already exists for the student and Course Offering.']);
        }
        $row = AttendanceExceptionRequest::create(['college_id' => $college->id, 'student_enrollment_id' => $enrollment->id, 'course_offering_id' => $courseOffering->id, 'academic_policy_id' => $evaluation['policy']->id, 'type' => $data['type'], 'status' => 'PENDING', 'attendance_percent_snapshot' => $evaluation['percent'], 'reason' => $data['reason'], 'supporting_reference' => $data['supporting_reference'] ?? null, 'requested_by' => $actor]);
        $this->audit('ATTENDANCE_EXCEPTION_REQUESTED', $row, $college, $actor, null, $row->toArray());

        return $row;
    }

    public function decide(College $college, AttendanceExceptionRequest $request, string $decision, string $remarks, int $actor): void
    {
        abort_unless((int) $request->college_id === (int) $college->id, 404);
        if ($request->status !== 'PENDING') {
            throw ValidationException::withMessages(['decision' => 'Only pending requests can be decided.']);
        }
        $before = $request->toArray();
        $request->update(['status' => $decision, 'decision_remarks' => $remarks, 'decided_at' => now(), 'decided_by' => $actor]);
        $this->audit('ATTENDANCE_EXCEPTION_'.$decision, $request, $college, $actor, $before, $request->fresh()->toArray());
    }

    public function finalize(College $college, StudentEnrollment $enrollment, CourseOffering $courseOffering, int $actor): StudentAttendanceEligibility
    {
        $e = $this->evaluate($college, $enrollment, $courseOffering);
        $row = StudentAttendanceEligibility::updateOrCreate(['student_enrollment_id' => $enrollment->id, 'course_offering_id' => $courseOffering->id], ['college_id' => $college->id, 'academic_policy_id' => $e['policy']->id, 'classes_held' => $e['held'], 'classes_attended' => $e['attended'], 'attendance_percent' => $e['percent'], 'basis' => $e['basis'], 'is_eligible' => $e['eligible'], 'finalized_at' => now(), 'finalized_by' => $actor]);
        $this->audit('ATTENDANCE_ELIGIBILITY_FINALIZED', $row, $college, $actor, null, $row->toArray());

        return $row;
    }

    private function assertScope(College $college, StudentEnrollment $enrollment, CourseOffering $offering): void
    {
        $offering->loadMissing('batch');
        abort_unless((int) $enrollment->college_id === (int) $college->id && (int) $offering->batch?->college_program_offering_id === (int) $enrollment->college_program_offering_id, 404);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function audit(string $event, Model $row, College $college, int $actor, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert(['actor_user_id' => $actor, 'event' => $event, 'resource_type' => class_basename($row), 'resource_id' => $row->getKey(), 'scope_type' => 'COLLEGE', 'scope_reference' => 'college:'.$college->id, 'before' => $before ? json_encode($before) : null, 'after' => $after ? json_encode($after) : null, 'created_at' => now()]);
    }
}
