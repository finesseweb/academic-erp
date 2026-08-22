# Curriculum Validation -> Approval Gate

## Status
IMPLEMENTED_IN_REPLACEMENT_PACKAGE — repository QA pending

## Frozen Business Rule
A Curriculum cannot be submitted merely because its current structure happens to be valid when Submit is clicked.

The user must explicitly run `Validate Structure` and obtain a PASS for the same structure being submitted.

## Checkpoint
PASS records:
- structure_validation_hash
- structure_validated_at
- structure_validated_by

Fingerprint covers the Curriculum header and implemented Terms, Slots and Course Mappings, including Credits and selection-rule values stored on Slots.

## Invalidation
No fragile manual 'validation = false' flag is required on every mutation.
The current fingerprint is recalculated. If any relevant value changed, it no longer matches the stored PASS hash and the checkpoint is stale.

## UI
Curriculum row:
- Structure
- Edit
- More

More:
- Submit for Approval — current PASS only
- Clone Structure
- Delete
- Retire / Restore

When eligible but not validated:
- show `Validate Structure First`
- do not expose an enabled Submit action

## Backend
Submission checks:
1. DRAFT and eligible approval status.
2. Active configured Curriculum workflow.
3. Current recorded validation fingerprint.
4. Fresh live validation.
5. Existing pending-request protection.

## Lifecycle
- Create -> DRAFT / NOT_SUBMITTED
- Validation PASS -> still DRAFT
- Submit -> DRAFT + SUBMITTED/UNDER_APPROVAL, structure locked
- Return/Reject -> DRAFT and editable
- Final approval -> ACTIVE + APPROVED
- Retire -> RETIRED

Direct form/API promotion to ACTIVE is prohibited.

## Audit
`CURRICULUM_STRUCTURE_VALIDATED`


## Submit Readiness Contract — 2026-08-22
Curriculum list API/Inertia data exposes:
- `structure_validation_current`
- `can_submit_for_approval`
- `submit_approval_hint`

Laravel calculates readiness from permission, active workflow, DRAFT lifecycle, eligible approval status and current validation fingerprint. UI uses this server result.

If Submit is unavailable, More shows the exact readiness hint such as `Validate Structure first.` or missing workflow/permission guidance.
