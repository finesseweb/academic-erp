<?php

namespace App\Services;

use App\Models\College;
use App\Models\CollegeAdmissionApplication;
use App\Models\CollegeAdmissionApplicationFieldValue;
use App\Models\CollegeAdmissionDocumentVerification;
use App\Models\CollegeAdmissionDocumentVerificationItem;
use App\Models\CollegeAdmissionSelectionRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CollegeAdmissionDocumentVerificationService
{
    public function screen(College $college, CollegeAdmissionSelectionRule $rule): array
    {
        $this->assertRuleOwnedByCollege($college, $rule);

        $applications = CollegeAdmissionApplication::query()
            ->where('college_id', $college->id)
            ->where('status', 'SUBMITTED')
            ->whereHas('choices.meritEntry', fn ($query) => $query->where('college_admission_selection_rule_id', $rule->id))
            ->with([
                'fieldValues.field:id,label,field_type,is_required',
                'documentVerification.items',
                'academicPreference.discipline:id,name,code',
                'academicPreference.specialization:id,name,code',
                'choices' => fn ($query) => $query
                    ->where('college_admission_selection_rule_id', $rule->id)
                    ->with('meritEntry:id,college_admission_application_choice_id,rank,final_weighted_score'),
            ])
            ->get()
            ->sortBy(fn ($application) => optional($application->choices->first()?->meritEntry)->rank ?? PHP_INT_MAX)
            ->values();

        return [
            'summary' => [
                'total' => $applications->count(),
                'verified' => $applications->where(fn ($app) => $app->documentVerification?->status === 'VERIFIED')->count(),
                'pending' => $applications->where(fn ($app) => ! $app->documentVerification || $app->documentVerification->status === 'PENDING')->count(),
                'deficient' => $applications->where(fn ($app) => $app->documentVerification?->status === 'DEFICIENT')->count(),
            ],
            'rows' => $applications->map(function (CollegeAdmissionApplication $application) {
                $verification = $application->documentVerification;
                $reviewByValue = $verification?->items?->keyBy('college_admission_application_field_value_id') ?? collect();
                $choice = $application->choices->first();
                $preference = $application->academicPreference;

                $documents = $application->fieldValues
                    ->filter(fn ($value) => $value->field && in_array($value->field->field_type, ['FILE', 'IMAGE'], true) && filled($value->file_path))
                    ->map(function ($value) use ($reviewByValue) {
                        $review = $reviewByValue->get($value->id);
                        return [
                            'field_value_id' => $value->id,
                            'field_id' => $value->college_admission_form_field_id,
                            'label' => $value->field?->label,
                            'field_type' => $value->field?->field_type,
                            'is_required' => (bool) $value->field?->is_required,
                            'file_name' => $value->file_name,
                            'file_mime' => $value->file_mime,
                            'file_size' => $value->file_size,
                            'review' => $review ? [
                                'status' => $review->status,
                                'remarks' => $review->remarks,
                                'reviewed_at' => optional($review->reviewed_at)->toIso8601String(),
                            ] : null,
                        ];
                    })->values()->all();

                return [
                    'application_id' => $application->id,
                    'application_no' => $application->application_no,
                    'candidate_name' => $application->candidate_name,
                    'rank' => (int) ($choice?->meritEntry?->rank ?? 0),
                    'final_weighted_score' => $choice?->meritEntry?->final_weighted_score !== null ? (float) $choice->meritEntry->final_weighted_score : null,
                    'discipline_name' => $preference?->discipline?->name,
                    'specialization_name' => $preference?->specialization?->name,
                    'verification' => $verification ? [
                        'id' => $verification->id,
                        'status' => $verification->status,
                        'notes' => $verification->notes,
                        'finalized_at' => optional($verification->finalized_at)->toIso8601String(),
                    ] : null,
                    'documents' => $documents,
                ];
            })->all(),
        ];
    }

    public function reviewItem(
        College $college,
        CollegeAdmissionApplicationFieldValue $fieldValue,
        string $status,
        ?string $remarks,
        int $actorId,
        ?string $ip
    ): CollegeAdmissionDocumentVerificationItem {
        $fieldValue->loadMissing(['application', 'field']);
        $application = $fieldValue->application;
        $this->assertApplicationOwnedByCollege($college, $application);

        if (! $fieldValue->field || ! in_array($fieldValue->field->field_type, ['FILE', 'IMAGE'], true) || blank($fieldValue->file_path)) {
            throw ValidationException::withMessages(['document' => 'Only an uploaded Admission FILE / IMAGE field can be reviewed as a document.']);
        }
        if (! in_array($status, ['VERIFIED', 'REJECTED', 'WAIVED'], true)) {
            throw ValidationException::withMessages(['status' => 'Select VERIFIED, REJECTED or WAIVED.']);
        }
        if (in_array($status, ['REJECTED', 'WAIVED'], true) && blank($remarks)) {
            throw ValidationException::withMessages(['remarks' => 'Remarks are required when a document is rejected or waived.']);
        }

        return DB::transaction(function () use ($college, $application, $fieldValue, $status, $remarks, $actorId, $ip) {
            $verification = CollegeAdmissionDocumentVerification::query()
                ->where('college_admission_application_id', $application->id)
                ->lockForUpdate()
                ->first();

            if (! $verification) {
                $verification = CollegeAdmissionDocumentVerification::create([
                    'college_id' => $college->id,
                    'college_admission_application_id' => $application->id,
                    'status' => 'PENDING',
                ]);
            }

            $item = CollegeAdmissionDocumentVerificationItem::query()
                ->where('college_admission_application_field_value_id', $fieldValue->id)
                ->lockForUpdate()
                ->first();
            $before = $item?->toArray();

            $payload = [
                'college_admission_document_verification_id' => $verification->id,
                'college_admission_application_field_value_id' => $fieldValue->id,
                'college_admission_form_field_id' => $fieldValue->college_admission_form_field_id,
                'field_label' => $fieldValue->field->label,
                'file_name' => $fieldValue->file_name,
                'status' => $status,
                'remarks' => filled($remarks) ? trim((string) $remarks) : null,
                'reviewed_at' => now(),
                'reviewed_by' => $actorId,
            ];

            if ($item) {
                $item->update($payload);
                $item = $item->fresh();
            } else {
                $item = CollegeAdmissionDocumentVerificationItem::create($payload);
            }

            if ($verification->status !== 'PENDING') {
                $verification->update([
                    'status' => 'PENDING',
                    'notes' => null,
                    'finalized_at' => null,
                    'finalized_by' => null,
                ]);
            }

            $this->audit('COLLEGE_ADMISSION_DOCUMENT_REVIEWED', $application->id, $college, $actorId, $ip, $before, $item->toArray());
            return $item;
        });
    }

    public function finalize(
        College $college,
        CollegeAdmissionApplication $application,
        string $status,
        ?string $notes,
        int $actorId,
        ?string $ip
    ): CollegeAdmissionDocumentVerification {
        $this->assertApplicationOwnedByCollege($college, $application);
        if ($application->status !== 'SUBMITTED') {
            throw ValidationException::withMessages(['verification' => 'Document Verification can be finalized only for a SUBMITTED application.']);
        }
        if (! in_array($status, ['VERIFIED', 'DEFICIENT'], true)) {
            throw ValidationException::withMessages(['status' => 'Finalize the application as VERIFIED or DEFICIENT.']);
        }
        if ($status === 'DEFICIENT' && blank($notes)) {
            throw ValidationException::withMessages(['notes' => 'Explain the document deficiency before finalizing as DEFICIENT.']);
        }

        return DB::transaction(function () use ($college, $application, $status, $notes, $actorId, $ip) {
            DB::table('college_admission_applications')->where('id', $application->id)->lockForUpdate()->get();
            $verification = CollegeAdmissionDocumentVerification::query()
                ->where('college_admission_application_id', $application->id)
                ->lockForUpdate()
                ->first();

            if (! $verification) {
                $verification = CollegeAdmissionDocumentVerification::create([
                    'college_id' => $college->id,
                    'college_admission_application_id' => $application->id,
                    'status' => 'PENDING',
                ]);
            }

            $documents = $application->fieldValues()
                ->whereNotNull('file_path')
                ->whereHas('field', fn ($query) => $query->whereIn('field_type', ['FILE', 'IMAGE']))
                ->get();
            $reviews = CollegeAdmissionDocumentVerificationItem::query()
                ->where('college_admission_document_verification_id', $verification->id)
                ->whereIn('college_admission_application_field_value_id', $documents->pluck('id'))
                ->get()
                ->keyBy('college_admission_application_field_value_id');

            if ($status === 'VERIFIED') {
                $unresolved = $documents->filter(function ($document) use ($reviews) {
                    $reviewStatus = $reviews->get($document->id)?->status;
                    return ! in_array($reviewStatus, ['VERIFIED', 'WAIVED'], true);
                });
                if ($unresolved->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'verification' => $unresolved->count().' uploaded document(s) are still pending/rejected. Verify or formally waive them before final VERIFIED status.',
                    ]);
                }
            }

            $before = $verification->toArray();
            $verification->update([
                'status' => $status,
                'notes' => filled($notes) ? trim((string) $notes) : null,
                'finalized_at' => now(),
                'finalized_by' => $actorId,
            ]);
            $fresh = $verification->fresh();
            $this->audit('COLLEGE_ADMISSION_DOCUMENT_VERIFICATION_FINALIZED', $application->id, $college, $actorId, $ip, $before, $fresh->toArray());
            return $fresh;
        });
    }

    private function assertRuleOwnedByCollege(College $college, CollegeAdmissionSelectionRule $rule): void
    {
        $rule->loadMissing('intake.offering');
        abort_unless((int) $rule->intake?->offering?->college_id === (int) $college->id, 404);
    }

    private function assertApplicationOwnedByCollege(College $college, ?CollegeAdmissionApplication $application): void
    {
        abort_unless($application && (int) $application->college_id === (int) $college->id, 404);
    }

    private function audit(string $event, int $applicationId, College $college, int $actorId, ?string $ip, ?array $before, array $after): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId,
            'event' => $event,
            'resource_type' => 'CollegeAdmissionDocumentVerification',
            'resource_id' => $applicationId,
            'scope_type' => 'COLLEGE',
            'scope_reference' => 'college:'.$college->id,
            'before' => $before ? json_encode($before) : null,
            'after' => json_encode($after),
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
