# 009 — Common Dynamic Approval Engine

## Status
APPROVED

## Decision
The ERP will use one reusable approval engine for all modules that require configurable approval. Curriculum and Academic Policy are the first supported subject types. Future approval-enabled modules must integrate with this same engine rather than introducing parallel approval tables, inboxes or workflow systems.

## Common responsibilities
- University/scope-aware workflow selection.
- `applies_to` subject-type enforcement.
- Ordered approval stages.
- Role-based approver assignment.
- Active/effective role validation.
- Approve / Return / Reject decisions.
- Configurable remarks requirements.
- Pending-stage routing and history.
- Authorization and audit trail.

## Subject-specific responsibilities
Each module owns:
- pre-submission/domain validation;
- eligibility to submit or resubmit;
- status/lifecycle transitions specific to that domain;
- final approval behavior such as activate, publish, lock or mark-current;
- amendment/version behavior where applicable.

## Backend integrity rule
The backend must reject a selected workflow unless all are true:
1. it belongs to the correct University/scope;
2. it is ACTIVE;
3. its `applies_to` exactly matches the subject type being submitted.

Frontend filtering does not replace this rule.

## Expansion rule
As additional subject types are integrated, prefer a registered subject-handler/strategy pattern over growing hard-coded controller branching. The reusable workflow engine remains the single source of approval mechanics.
