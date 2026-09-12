# ADR 075 — Admission Form Builder Delete Action Integrity

## Status
Accepted — 2026-09-01

## Context
Admission Form Step, Panel and Field delete controls were visible on DRAFT templates, but the UI relied on passive DELETE forms and did not surface dependency failures clearly. Step deletion also had a database-integrity edge case: answer conditions, cross-field comparisons and copy rules use RESTRICT source-field foreign keys, so deleting a step containing internally-related fields could fail even though every related field belonged to the same DRAFT step.

## Decision
Admission Form structural deletion remains available only on DRAFT templates and must be explicit, dependency-safe and user-visible.

Rules:
- Delete Draft, Delete Step, Delete Panel, Delete Field and Remove Mapping use explicit Inertia router DELETE requests.
- Every destructive action requires a confirmation prompt and disables itself while the request is processing.
- Backend validation errors must be surfaced to the operator rather than appearing as a no-op.
- Panel deletion keeps its fields and moves them to the direct Step field collection.
- Field deletion remains blocked when the field is referenced by another condition, comparison, copy rule or submitted application value.
- Step deletion is blocked when any contained field has submitted application values or is referenced by a field outside the Step.
- Internal condition/comparison/copy rules owned entirely by fields inside the Step are removed transactionally before deleting the Step so RESTRICT source-field foreign keys cannot cause a false failure.
- ACTIVE and RETIRED/SUPERSEDED templates remain structurally frozen under ADR 053.

## Implementation
Backend:
- `app/Http/Controllers/UniversityAdmissionFormSetupController.php`
- `app/Http/Controllers/CollegeAdmissionFormSetupController.php`

Frontend:
- `resources/js/pages/admin/admission-form-setup/index.tsx`
- `resources/js/pages/college-admission-form-setup/index.tsx`

No database migration is required.
