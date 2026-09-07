<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmCollegeAdmissionRequest;
use App\Http\Requests\RevokeCollegeAdmissionRequest;
use App\Models\Admission;
use App\Models\College;
use App\Models\CollegeAdmissionSeatAllocation;
use App\Models\CollegeAdmissionSelectionRule;
use App\Services\CollegeAdmissionConfirmationService;
use App\Services\ApplicableFeeDemandService;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollegeAdmissionConfirmationController extends Controller
{
    public function index(
        Request $request,
        College $college,
        CollegeAdmissionConfirmationService $service
    ): Response {
        $this->authorizeCollege($request, $college, 'college_admission_confirmation.view');

        $ruleIds = CollegeAdmissionSeatAllocation::query()
            ->where('college_id', $college->id)
            ->distinct()
            ->pluck('college_admission_selection_rule_id');

        $rules = CollegeAdmissionSelectionRule::query()
            ->whereIn('id', $ruleIds)
            ->with([
                'intake.offering.programTemplate:id,name,code',
                'intake.offering.academicSession:id,name,code,is_current',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(fn (CollegeAdmissionSelectionRule $rule) => [
                'id' => $rule->id,
                'name' => $rule->name,
                'code' => $rule->code,
                'version_no' => (int) $rule->version_no,
                'program_name' => $rule->intake?->offering?->programTemplate?->name,
                'program_code' => $rule->intake?->offering?->programTemplate?->code,
                'session_name' => $rule->intake?->offering?->academicSession?->name,
            ])
            ->values();

        $requestedRuleId = (int) $request->query('rule_id', 0);
        $selectedRuleId = $requestedRuleId > 0 && $rules->contains(fn ($row) => (int) $row['id'] === $requestedRuleId)
            ? $requestedRuleId
            : null;

        return Inertia::render('college-admission-confirmations/index', [
            'college' => $college->only(['id', 'name', 'code', 'status']),
            'rules' => $rules,
            'selectedRuleId' => $selectedRuleId,
            'screen' => $service->screen($college, $selectedRuleId),
            'can' => [
                'confirm' => $request->user()->hasCollegePermission('college_admission_confirmation.confirm', $college->id),
                'revoke' => $request->user()->hasCollegePermission('college_admission_confirmation.revoke', $college->id),
            ],
        ]);
    }

    public function confirm(
        ConfirmCollegeAdmissionRequest $request,
        College $college,
        CollegeAdmissionSeatAllocation $allocation,
        CollegeAdmissionConfirmationService $service,
        ApplicableFeeDemandService $feeDemandService
    ): RedirectResponse {
        $this->authorizeCollege($request, $college, 'college_admission_confirmation.confirm');
        $admission = $service->confirm(
            $college,
            $allocation,
            $request->validated('decision_note'),
            $request->user()->id,
            $request->ip()
        );

        $feeMessage = ' No initial fee demand was required because no applicable ACTIVE Period 1 / ONE_TIME fee items were found.';
        try {
            $demand = $feeDemandService->generateInitialForAdmission($college, $admission, $request->user()->id);
            if ($demand) {
                $feeMessage = ' Initial applicable fee demand '.$demand->demand_no.' is ready automatically.';
            }
        } catch (ValidationException $exception) {
            $messages = collect($exception->errors())->flatten()->filter()->values();
            $feeMessage = ' Admission is confirmed, but automatic fee demand generation needs attention: '.($messages->first() ?: 'Fee configuration could not be resolved.');
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Admission confirmed as '.$admission->admission_no.'.'.$feeMessage,
        ]);
    }

    public function revoke(
        RevokeCollegeAdmissionRequest $request,
        College $college,
        Admission $admission,
        CollegeAdmissionConfirmationService $service
    ): RedirectResponse {
        $this->authorizeCollege($request, $college, 'college_admission_confirmation.revoke');
        $service->revoke(
            $college,
            $admission,
            $request->validated('reason'),
            $request->user()->id,
            $request->ip()
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Admission Confirmation revoked. The seat remains allocated until Seat Allocation is explicitly cancelled or re-used according to policy.',
        ]);
    }

    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
