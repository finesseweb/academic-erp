# Academic Approval — Phase 5B Execution

## Status
IMPLEMENTED IN REPLACEMENT PACKAGE

## Curriculum Approval Flow
1. Curriculum remains DRAFT while being prepared.
2. Validate Structure must pass.
3. User selects an ACTIVE Curriculum approval workflow.
4. Submit creates an approval request and snapshots all active workflow stages.
5. Curriculum becomes read-only while approval is pending.
6. Current approver is resolved from existing active User Role assignments.
7. Approver may Approve, Return, or Reject.
8. Approve advances to the next stage.
9. Final stage approval sets:
   - approval_status = APPROVED
   - lifecycle_status = ACTIVE
10. Return or Reject keeps lifecycle DRAFT and unlocks correction/resubmission.

## Approval Status
- NOT_SUBMITTED
- SUBMITTED
- UNDER_APPROVAL
- RETURNED
- REJECTED
- APPROVED

## Source / Approver Rules
No officer name is hard-coded.
The stage stores approver_role_id.
At decision time, the logged-in user must have an ACTIVE and effective user_roles assignment for that role.

## Structure Lock
While approval_status is SUBMITTED / UNDER_APPROVAL / APPROVED:
- Curriculum Header edit blocked
- Term/Semester mutation blocked
- Slot mutation blocked
- Course Mapping mutation blocked
- Hard Delete blocked
- Clone into that Curriculum blocked

Review remains available.

## Decision Rules
Approve:
- remarks optional
- advance next stage, or activate Curriculum on final stage

Return:
- remarks required when workflow stage policy requires it
- request completes as RETURNED
- Curriculum returns to editable DRAFT

Reject:
- remarks required when workflow stage policy requires it
- request completes as REJECTED
- Curriculum returns to editable DRAFT

## History
Every request stores a stage snapshot so later changes to Workflow Setup do not rewrite historical approvals.

## Gate 5
Core Curriculum workflow execution is now implemented.
Before Gate 5 is formally marked PASS, run migration/build and test:
- submit validation
- structure lock
- role-based inbox
- multi-level advance
- return/reject resubmission
- final activation
- permission-denied cases
- audit history
