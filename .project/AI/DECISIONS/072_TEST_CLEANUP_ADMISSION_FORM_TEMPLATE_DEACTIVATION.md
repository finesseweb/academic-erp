# ADR 072 — Testing-Only Admission Form Template Deactivation

Date: 2026-09-01
Status: Accepted

## Context
Admission Form Templates are structurally frozen after activation. Normal Admission Form Setup must not expose an `ACTIVE -> DRAFT` lifecycle transition because live/historical forms must not be mutated in ordinary operation.

During development and owner QA, an incorrectly activated test template may nevertheless need to be returned to DRAFT so its structure can be corrected without deleting and rebuilding the complete template.

## Decision
The existing **System Maintenance -> Test Data Cleanup** center is the only supported exception for returning an Admission Form Template from `ACTIVE` to `DRAFT`.

A new testing-only action is provided under **Admission Form Templates**:

`Deactivate for Testing`

The action:
- is available only when the template status is `ACTIVE`;
- requires the existing `test_data_cleanup.manage` permission;
- requires the existing Test Data Cleanup environment guard;
- requires the exact Admission Form Template Code as confirmation;
- changes only the template lifecycle status from `ACTIVE` to `DRAFT`;
- preserves the template, Steps, Panels, Fields, rules, mappings and existing Application records;
- automatically disables any enabled public applicant mappings for that template before the template becomes DRAFT;
- writes audit event `TEST_ADMISSION_FORM_TEMPLATE_DEACTIVATED` with before/after state.

## Boundary
This is a **testing/maintenance recovery action**, not a normal Admission Form lifecycle feature.

Normal University/College Admission Form Setup continues to keep ACTIVE templates frozen. It must not gain a standard Deactivate button merely because this maintenance action exists.

The future Draft Revision/version workflow from ADR 053 remains the correct production-safe mechanism for changing a live form version.

## Database
No schema change is required.

## Security
No new permission is introduced. The action reuses `test_data_cleanup.manage` and the Test Data Cleanup environment switch.
