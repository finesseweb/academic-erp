<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertAcademicPolicyAttendanceRuleRequest;
use App\Models\AcademicPolicy;
use App\Models\AcademicPolicyAttendanceRule;
use App\Services\AcademicPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AcademicPolicyAttendanceRuleController extends Controller
{
    public function __construct(private readonly AcademicPolicyService $policyService) {}

    public function edit(Request $request, AcademicPolicy $academicPolicy): Response
    {
        abort_unless($request->user()->hasPermission('academic_policy.view'), 403);

        $academicPolicy->load([
            'academicSession:id,name,code',
            'programTemplate:id,name,code',
            'curriculum:id,name,code,version',
            'attendanceRule',
        ]);

        return Inertia::render('admin/academic-policies/attendance', [
            'policy' => $academicPolicy,
            'rule' => $academicPolicy->attendanceRule,
            'editable' => $request->user()->hasPermission('academic_policy.update') &&
                $academicPolicy->lifecycle_status === 'DRAFT' &&
                ! in_array(
                    $academicPolicy->approval_status ?? 'NOT_SUBMITTED',
                    ['SUBMITTED', 'UNDER_APPROVAL', 'APPROVED'],
                    true
                ),
        ]);
    }

    public function update(
        UpsertAcademicPolicyAttendanceRuleRequest $request,
        AcademicPolicy $academicPolicy
    ): RedirectResponse {
        $this->policyService->assertEditable($academicPolicy);
        $data = $request->validated();

        $minimum = (float) $data['minimum_attendance_percent'];

        if (! $data['allow_condonation']) {
            $data['condonation_minimum_percent'] = null;
            $data['maximum_condonable_shortage_percent'] = null;
        } else {
            if ($data['condonation_minimum_percent'] === null) {
                throw ValidationException::withMessages([
                    'condonation_minimum_percent' =>
                        'Condonation Minimum Attendance is required when condonation is allowed.',
                ]);
            }

            $condonationMinimum = (float) $data['condonation_minimum_percent'];
            if ($condonationMinimum >= $minimum) {
                throw ValidationException::withMessages([
                    'condonation_minimum_percent' =>
                        'Condonation Minimum Attendance must be lower than the normal Minimum Attendance.',
                ]);
            }

            if ($data['maximum_condonable_shortage_percent'] !== null) {
                $shortage = (float) $data['maximum_condonable_shortage_percent'];
                if ($shortage > $minimum) {
                    throw ValidationException::withMessages([
                        'maximum_condonable_shortage_percent' =>
                            'Maximum condonable shortage cannot exceed Minimum Attendance.',
                    ]);
                }
            }
        }

        DB::transaction(function () use ($academicPolicy, $data, $request) {
            $existing = AcademicPolicyAttendanceRule::query()
                ->where('academic_policy_id', $academicPolicy->id)
                ->first();

            $before = $existing?->toArray();

            $rule = AcademicPolicyAttendanceRule::query()->updateOrCreate(
                ['academic_policy_id' => $academicPolicy->id],
                [
                    ...$data,
                    'created_by' => $existing?->created_by ?? $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]
            );

            $this->policyService->clearValidationCheckpoint($academicPolicy);

            DB::table('audit_logs')->insert([
                'event' => $existing
                    ? 'ACADEMIC_POLICY_ATTENDANCE_UPDATED'
                    : 'ACADEMIC_POLICY_ATTENDANCE_CREATED',
                'resource_type' => 'academic_policy_attendance_rule',
                'resource_id' => $rule->id,
                'before' => $before ? json_encode($before) : null,
                'after' => json_encode($rule->fresh()->toArray()),
                'actor_user_id' => $request->user()->id,
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Attendance Policy saved successfully.');
    }
}
