<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\CollegeAdmissionApplication;
use App\Models\CollegeAdmissionApplicationFieldValue;
use App\Models\CollegeAdmissionMeritEntry;
use App\Models\CollegeAdmissionSelectionRule;
use App\Services\CollegeAdmissionDocumentVerificationService;
use App\Services\CollegeReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CollegeAdmissionDocumentVerificationController extends Controller
{
    public function index(
        Request $request,
        College $college,
        CollegeAdmissionDocumentVerificationService $service,
        CollegeReservationService $reservationService
    ): Response {
        $this->authorizeCollege($request, $college, 'college_admission_document_verification.view');

        $ruleIds = CollegeAdmissionMeritEntry::query()
            ->where('college_id', $college->id)
            ->distinct()
            ->pluck('college_admission_selection_rule_id');

        $rules = CollegeAdmissionSelectionRule::query()
            ->whereIn('id', $ruleIds)
            ->with([
                'intake.offering.programTemplate.degree.degreeLevel:id,name,code',
                'intake.offering.academicSession:id,name,code,is_current',
                'reservationPlan:id,college_program_intake_id,bucket_type,bucket_key,basis_capacity,status',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(function (CollegeAdmissionSelectionRule $rule) use ($reservationService) {
                $bucket = $reservationService->availableBuckets($rule->intake)->firstWhere('bucket_key', $rule->bucket_key);
                $program = $rule->intake?->offering?->programTemplate;
                $degree = $program?->degree;
                $degreeLevel = $degree?->degreeLevel;

                return [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'code' => $rule->code,
                    'version_no' => $rule->version_no,
                    'status' => $rule->status,
                    'bucket_key' => $rule->bucket_key,
                    'bucket_label' => $bucket['label'] ?? $rule->bucket_key,
                    'basis_capacity' => (int) $rule->basis_capacity,
                    'program_name' => $program?->name,
                    'program_code' => $program?->code,
                    'degree_level_name' => $degreeLevel?->name,
                    'degree_name' => $degree?->name,
                    'session_name' => $rule->intake?->offering?->academicSession?->name,
                ];
            })
            ->values();

        $selectedRuleId = (int) $request->query('rule_id', $rules->first()['id'] ?? 0);
        $selectedRule = $selectedRuleId > 0
            ? CollegeAdmissionSelectionRule::query()->find($selectedRuleId)
            : null;

        if ($selectedRule && ! $rules->contains(fn ($row) => (int) $row['id'] === (int) $selectedRule->id)) {
            $selectedRule = null;
        }

        return Inertia::render('college-admission-document-verification/index', [
            'college' => $college->only(['id', 'name', 'code', 'status']),
            'rules' => $rules,
            'selectedRuleId' => $selectedRule?->id,
            'screen' => $selectedRule ? $service->screen($college, $selectedRule) : null,
            'can' => [
                'review' => $request->user()->hasCollegePermission('college_admission_document_verification.review', $college->id),
                'finalize' => $request->user()->hasCollegePermission('college_admission_document_verification.finalize', $college->id),
            ],
        ]);
    }

    public function review(
        Request $request,
        College $college,
        CollegeAdmissionApplicationFieldValue $fieldValue,
        CollegeAdmissionDocumentVerificationService $service
    ): RedirectResponse {
        $this->authorizeCollege($request, $college, 'college_admission_document_verification.review');
        $data = $request->validate([
            'status' => ['required', 'in:VERIFIED,REJECTED,WAIVED'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->reviewItem($college, $fieldValue, $data['status'], $data['remarks'] ?? null, $request->user()->id, $request->ip());

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Document review saved. Overall verification returned to PENDING if it had already been finalized.',
        ]);
    }

    public function finalize(
        Request $request,
        College $college,
        CollegeAdmissionApplication $application,
        CollegeAdmissionDocumentVerificationService $service
    ): RedirectResponse {
        $this->authorizeCollege($request, $college, 'college_admission_document_verification.finalize');
        $data = $request->validate([
            'status' => ['required', 'in:VERIFIED,DEFICIENT'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $verification = $service->finalize($college, $application, $data['status'], $data['notes'] ?? null, $request->user()->id, $request->ip());

        return back()->with('toast', [
            'type' => 'success',
            'message' => $verification->status === 'VERIFIED'
                ? 'Application documents verified. This candidate is now eligible to proceed to Seat Allocation.'
                : 'Application marked DEFICIENT. Seat Allocation remains blocked until documents are resolved and re-verified.',
        ]);
    }

    public function download(
        Request $request,
        College $college,
        CollegeAdmissionApplicationFieldValue $fieldValue
    ): StreamedResponse {
        $this->authorizeCollege($request, $college, 'college_admission_document_verification.view');
        $fieldValue->loadMissing(['application', 'field']);

        abort_unless(
            $fieldValue->application
            && (int) $fieldValue->application->college_id === (int) $college->id
            && $fieldValue->field
            && in_array($fieldValue->field->field_type, ['FILE', 'IMAGE'], true),
            404
        );
        abort_unless(filled($fieldValue->file_path) && Storage::disk('local')->exists($fieldValue->file_path), 404);

        $downloadName = $fieldValue->file_name ?: basename($fieldValue->file_path);
        return Storage::disk('local')->download($fieldValue->file_path, $downloadName);
    }

    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
