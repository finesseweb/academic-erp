# Academic Approval Inbox — Dynamic Subject Execution

Status: IMPLEMENTED

The common `/admin/academic-approval/inbox` is subject-aware. It currently supports:

- `CURRICULUM`
- `ACADEMIC_POLICY`

The inbox must never join only one subject table globally.

## Pending table

Columns:
- Type
- Item
- Workflow
- Current Level
- Actions

`Type` identifies Curriculum vs Academic Policy.

`Item` resolves name/code/version from the corresponding subject table.

## Review action

- Curriculum → Curriculum structure review.
- Academic Policy → Academic Policies context.

## Decision routing

The common controller routes the decision according to `approval_requests.subject_type`:

- `CURRICULUM` → existing `ApprovalRequestService`.
- `ACADEMIC_POLICY` → `AcademicPolicyApprovalService`.

This preserves each subject's final lifecycle behavior while keeping one common Inbox UI.

## History

Approval History is also subject-aware and displays Type + Item + Workflow + stage history.

## Extension rule

Future academic approval resources must add a supported subject adapter/resolution branch; do not hard-code the Inbox back to one module.
