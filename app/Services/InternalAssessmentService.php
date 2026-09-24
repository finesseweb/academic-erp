<?php

namespace App\Services;

use App\Models\College;
use App\Models\CourseOffering;
use App\Models\FacultyAllocation;
use App\Models\InternalAssessmentActivity;
use App\Models\InternalAssessmentActivityStudent;
use App\Models\InternalAssessmentComponent;
use App\Models\InternalAssessmentMark;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InternalAssessmentService
{
    public function __construct(private AcademicPolicyResolverService $policies) {}

    /** @param array<string,mixed> $data */
    public function saveComponent(College $college, array $data, int $actor, ?InternalAssessmentComponent $component = null): InternalAssessmentComponent
    {
        $offering = $this->offering($college, (int) $data['course_offering_id']);
        $policy = $this->policies->resolveForOffering($offering->batch->offering);
        $policy?->loadMissing('assessmentExamRule');
        if (! $policy || ! $policy->assessmentExamRule) {
            throw ValidationException::withMessages(['academic_policy' => 'An applicable ACTIVE + APPROVED Academic Policy with Assessment / Examination Rules is required.']);
        }
        if ($component) {
            $this->assertComponentScope($college, $component);
            if ($component->status !== 'DRAFT') {
                throw ValidationException::withMessages(['component' => 'Only DRAFT components can be edited.']);
            }
        }
        $otherWeight = (float) InternalAssessmentComponent::where('course_offering_id', $offering->id)->whereIn('status', ['DRAFT', 'ACTIVE'])->when($component, fn ($q) => $q->whereKeyNot($component->id))->sum('weightage_percent');
        if ($otherWeight + (float) $data['weightage_percent'] > 100.0001) {
            throw ValidationException::withMessages(['weightage_percent' => 'Active and draft component weightage cannot exceed 100% for the Course Offering.']);
        }
        if ((float) ($data['minimum_pass_marks'] ?? 0) > (float) $data['maximum_marks']) {
            throw ValidationException::withMessages(['minimum_pass_marks' => 'Minimum pass marks cannot exceed maximum marks.']);
        }

        return DB::transaction(function () use ($college, $data, $actor, $component, $offering, $policy) {
            $before = $component?->toArray();
            $values = ['course_offering_id' => $offering->id, 'academic_policy_id' => $policy->id, 'component_type' => $data['component_type'], 'name' => $data['name'], 'maximum_marks' => $data['maximum_marks'], 'weightage_percent' => $data['weightage_percent'], 'minimum_pass_marks' => $data['minimum_pass_marks'] ?? null, 'display_order' => $data['display_order'], 'notes' => $data['notes'] ?? null, 'updated_by' => $actor];
            if ($component) {
                $component->update($values);
            } else {
                $component = InternalAssessmentComponent::create($values + ['status' => 'DRAFT', 'created_by' => $actor]);
            }
            $this->audit($before ? 'INTERNAL_ASSESSMENT_COMPONENT_UPDATED' : 'INTERNAL_ASSESSMENT_COMPONENT_CREATED', $component, $college, $actor, $before, $component->fresh()->toArray());

            return $component;
        });
    }

    public function componentStatus(College $college, InternalAssessmentComponent $component, string $status, int $actor): void
    {
        $this->assertComponentScope($college, $component);
        if ($component->status === $status) {
            return;
        }
        if ($status === 'ACTIVE' && $component->status !== 'DRAFT') {
            throw ValidationException::withMessages(['status' => 'Only DRAFT components can be activated.']);
        }
        if ($status === 'INACTIVE' && $component->activities()->whereIn('status', ['DRAFT', 'PUBLISHED'])->exists()) {
            throw ValidationException::withMessages(['status' => 'Close all linked activities before deactivating this component.']);
        }
        $before = ['status' => $component->status];
        $component->update(['status' => $status, 'updated_by' => $actor]);
        $this->audit('INTERNAL_ASSESSMENT_COMPONENT_'.$status, $component, $college, $actor, $before, ['status' => $status]);
    }

    /** @param array<string,mixed> $data */
    public function saveActivity(College $college, string $type, array $data, int $actor, ?InternalAssessmentActivity $activity = null): InternalAssessmentActivity
    {
        $component = InternalAssessmentComponent::with('courseOffering.batch.offering')->whereKey($data['internal_assessment_component_id'])->first();
        if (! in_array($type, ['ASSIGNMENT', 'QUIZ', 'MID_SEMESTER', 'PRACTICAL'], true) || ! $component || (int) $component->courseOffering?->batch?->offering?->college_id !== (int) $college->id || $component->component_type !== $type || $component->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['internal_assessment_component_id' => "Select an ACTIVE {$type} component belonging to this College."]);
        }
        if ($activity) {
            $this->assertActivityScope($college, $activity);
            if ($activity->status !== 'DRAFT') {
                throw ValidationException::withMessages(['activity' => 'Only DRAFT activities can be edited.']);
            }
        }
        $allocation = FacultyAllocation::whereKey($data['faculty_allocation_id'])->where('course_offering_id', $component->course_offering_id)->where('status', 'ACTIVE')->first();
        if (! $allocation) {
            throw ValidationException::withMessages(['faculty_allocation_id' => 'Select an ACTIVE Faculty Allocation for the same Course Offering.']);
        }
        $opens = Carbon::parse($data['opens_at']);
        $closes = Carbon::parse($data['closes_at']);
        if (! $closes->greaterThan($opens)) {
            throw ValidationException::withMessages(['closes_at' => 'Close/due time must be after the opening time.']);
        }
        if (in_array($type, ['QUIZ', 'MID_SEMESTER', 'PRACTICAL'], true) && empty($data['duration_minutes'])) {
            throw ValidationException::withMessages(['duration_minutes' => ucfirst(strtolower(str_replace('_', ' ', $type))).' duration is required.']);
        }
        $this->assertWithinTerm($component, $opens, $closes);

        return DB::transaction(function () use ($college, $type, $data, $actor, $activity, $component, $allocation) {
            $before = $activity?->toArray();
            $values = ['internal_assessment_component_id' => $component->id, 'faculty_allocation_id' => $allocation->id, 'title' => $data['title'], 'instructions' => $data['instructions'] ?? null, 'opens_at' => $data['opens_at'], 'closes_at' => $data['closes_at'], 'duration_minutes' => in_array($type, ['QUIZ', 'MID_SEMESTER', 'PRACTICAL'], true) ? $data['duration_minutes'] : null, 'updated_by' => $actor];
            if ($activity) {
                $activity->update($values);
            } else {
                $activity = InternalAssessmentActivity::create($values + ['status' => 'DRAFT', 'created_by' => $actor]);
            }$this->audit($before ? 'INTERNAL_ASSESSMENT_ACTIVITY_UPDATED' : 'INTERNAL_ASSESSMENT_ACTIVITY_CREATED', $activity, $college, $actor, $before, $activity->fresh()->toArray());

            return $activity;
        });
    }

    public function activityStatus(College $college, InternalAssessmentActivity $activity, string $status, int $actor): void
    {
        $this->assertActivityScope($college, $activity);
        $activity->loadMissing(['component.courseOffering.batch.offering', 'facultyAllocation']);
        $allowed = ['DRAFT' => ['PUBLISHED'], 'PUBLISHED' => ['CLOSED'], 'CLOSED' => []];
        if (! in_array($status, $allowed[$activity->status], true)) {
            throw ValidationException::withMessages(['status' => 'Invalid activity lifecycle transition.']);
        }
        DB::transaction(function () use ($activity, $status, $college, $actor) {
            if ($status === 'PUBLISHED' && ($activity->component->status !== 'ACTIVE' || $activity->component->courseOffering->status !== 'ACTIVE' || $activity->component->courseOffering->batch->status !== 'ACTIVE' || $activity->component->courseOffering->batch->offering?->status !== 'ACTIVE' || $activity->facultyAllocation?->status !== 'ACTIVE')) {
                throw ValidationException::withMessages(['status' => 'Publishing requires an ACTIVE component, Course Offering, Batch, Programme Offering and Faculty Allocation.']);
            }
            if ($status === 'PUBLISHED') {
                $courseOffering = $activity->component->courseOffering;
                $allocation = $activity->facultyAllocation;
                $rosterIds = DB::table('student_enrollments as se')->where('se.college_id', $college->id)->where('se.status', 'ENROLLED')->where('se.college_program_offering_id', $courseOffering->batch->college_program_offering_id)->where('se.batch_id', $courseOffering->batch_id)->when($allocation->section_id, fn ($q) => $q->where('se.section_id', $allocation->section_id))->whereExists(fn ($q) => $q->selectRaw('1')->from('student_enrollment_course_choices as sec')->whereColumn('sec.student_enrollment_id', 'se.id')->where('sec.curriculum_course_mapping_id', $courseOffering->curriculum_course_mapping_id))->pluck('se.id');
                if ($rosterIds->isEmpty()) {
                    throw ValidationException::withMessages(['status' => 'Publishing requires at least one canonically enrolled student in the exact Course Offering and Faculty Allocation scope.']);
                }
                foreach ($rosterIds as $enrollmentId) {
                    DB::table('internal_assessment_activity_students')->updateOrInsert(['internal_assessment_activity_id' => $activity->id, 'student_enrollment_id' => $enrollmentId], ['assigned_at' => now(), 'assigned_by' => $actor, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            $before = ['status' => $activity->status];
            $activity->update(['status' => $status, 'updated_by' => $actor]);
            $this->audit('INTERNAL_ASSESSMENT_ACTIVITY_'.$status, $activity, $college, $actor, $before, ['status' => $status]);
        });
    }

    /** @param array<int, array<string, mixed>> $records */
    public function saveMarks(College $college, InternalAssessmentActivity $activity, array $records, int $actor): void
    {
        $this->assertActivityScope($college, $activity);
        $activity->loadMissing('component');
        if (! in_array($activity->status, ['PUBLISHED', 'CLOSED'], true)) {
            throw ValidationException::withMessages(['activity' => 'Marks can be entered only after the activity is published.']);
        }
        $rosterIds = InternalAssessmentActivityStudent::where('internal_assessment_activity_id', $activity->id)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $submittedIds = collect($records)->pluck('internal_assessment_activity_student_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        if ($rosterIds === [] || $rosterIds !== $submittedIds || count($submittedIds) !== count(array_unique($submittedIds))) {
            throw ValidationException::withMessages(['records' => 'Marks Entry must include every published roster student exactly once. Refresh and try again.']);
        }
        DB::transaction(function () use ($records, $activity, $college, $actor) {
            foreach ($records as $record) {
                $status = $record['result_status'];
                $marks = $status === 'ABSENT' ? null : (float) $record['marks_obtained'];
                if ($status === 'ENTERED' && ($marks < 0 || $marks > (float) $activity->component->maximum_marks)) {
                    throw ValidationException::withMessages(['records' => "Marks must be between 0 and {$activity->component->maximum_marks}."]);
                }
                $row = InternalAssessmentMark::where('internal_assessment_activity_student_id', $record['internal_assessment_activity_student_id'])->lockForUpdate()->first();
                $before = $row?->toArray();
                $values = ['result_status' => $status, 'marks_obtained' => $marks, 'remarks' => $record['remarks'] ?? null, 'entered_at' => now(), 'entered_by' => $actor, 'revision_no' => $row ? $row->revision_no + 1 : 0];
                if ($row) {
                    $row->update($values);
                } else {
                    $row = InternalAssessmentMark::create($values + ['internal_assessment_activity_student_id' => $record['internal_assessment_activity_student_id']]);
                }
                $this->audit($before ? 'INTERNAL_ASSESSMENT_MARK_CORRECTED' : 'INTERNAL_ASSESSMENT_MARK_ENTERED', $row, $college, $actor, $before, $row->fresh()->toArray());
            }
        });
    }

    private function offering(College $college, int $id): CourseOffering
    {
        $row = CourseOffering::with(['batch.offering', 'curriculumCourseMapping.slot.term'])->whereKey($id)->whereHas('batch.offering', fn ($q) => $q->where('college_id', $college->id))->first();
        if (! $row) {
            throw ValidationException::withMessages(['course_offering_id' => 'Select a Course Offering belonging to this College.']);
        }

        return $row;
    }

    private function assertComponentScope(College $college, InternalAssessmentComponent $row): void
    {
        abort_unless(InternalAssessmentComponent::whereKey($row->id)->whereHas('courseOffering.batch.offering', fn ($q) => $q->where('college_id', $college->id))->exists(), 404);
    }

    private function assertActivityScope(College $college, InternalAssessmentActivity $row): void
    {
        abort_unless(InternalAssessmentActivity::whereKey($row->id)->whereHas('component.courseOffering.batch.offering', fn ($q) => $q->where('college_id', $college->id))->exists(), 404);
    }

    private function assertWithinTerm(InternalAssessmentComponent $component, Carbon $opens, Carbon $closes): void
    {
        $component->loadMissing('courseOffering.batch.offering', 'courseOffering.curriculumCourseMapping.slot.term');
        $offering = $component->courseOffering->batch->offering;
        $termId = $component->courseOffering->curriculumCourseMapping->slot->curriculum_term_id;
        $valid = DB::table('academic_calendar_term_periods as p')->join('academic_calendars as c', 'c.id', '=', 'p.academic_calendar_id')->join('college_academic_calendars as cc', 'cc.university_academic_calendar_id', '=', 'c.id')->where('cc.college_id', $offering->college_id)->where('cc.status', 'ACTIVE')->where('c.status', 'ACTIVE')->where('c.academic_session_id', $offering->academic_session_id)->where('p.curriculum_term_id', $termId)->where('p.status', 'ACTIVE')->whereDate('p.start_date', '<=', $opens->toDateString())->whereDate('p.end_date', '>=', $closes->toDateString())->exists();
        if (! $valid) {
            throw ValidationException::withMessages(['opens_at' => 'Activity opening and closing dates must fall inside the active Academic Calendar period for this Curriculum Term.']);
        }
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
