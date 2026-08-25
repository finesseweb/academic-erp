# Current Implementation State

## Academic Policy scope enhancement

**IMPLEMENTED IN REPLACEMENT PACKAGE — QA PENDING:** Academic Policy now supports the governed scope hierarchy `UNIVERSITY -> DEGREE_LEVEL -> PROGRAM_TEMPLATE -> CURRICULUM`. Degree Level scope uses the existing active same-University Degree Level Master and is persisted through `academic_policies.degree_level_id`.


## Common Academic Approval integration

**IMPLEMENTED — QA PENDING:** Curriculum and Academic Policy are both integrated into the common Academic Approval engine with role-based ordered stages, Approve / Return / Reject, resubmission support and shared Inbox/history.

### Integrity correction
Academic Policy workflow submission now requires the selected workflow to:
- belong to the same University;
- be ACTIVE; and
- have `applies_to = ACADEMIC_POLICY`.

This matches the existing Curriculum workflow-type enforcement.

### Frozen future rule
Academic Approval is a reusable engine. Future approval-enabled modules must integrate with the same workflow/stage/inbox mechanics while keeping subject-specific validation and final lifecycle behavior in their own handler/service.

### Current gate
Run the Approval QA defined in `NEXT_WORKFLOW.md` before starting Academic Calendar.
