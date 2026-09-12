# Academic Policy — Approval, Activation & Amendment Lifecycle

Status: IMPLEMENTED

## Lifecycle

```text
DRAFT
→ configure all policy sections
→ Validate Complete Policy
→ current validation PASS
→ Submit for Approval
→ role-based approval stages
→ final APPROVE
→ ACTIVE + APPROVED + Current Version
```

RETURN / REJECT:
- approval request completes as RETURNED / REJECTED;
- Academic Policy returns to editable DRAFT;
- correction + revalidation + resubmission is allowed.

## Submission gate

Backend requires:
- lifecycle = DRAFT;
- approval status = NOT_SUBMITTED / RETURNED / REJECTED;
- current Academic Policy validation fingerprint PASS;
- one live validation PASS at submission;
- active selected workflow with active stages;
- no existing pending policy approval request.

`approval_requests.subject_type = ACADEMIC_POLICY`.

Workflow stages are snapshotted into existing `approval_request_stages`.

## Approval inbox

Route:
`/admin/academic-policies/approval-inbox`

The inbox uses the existing role assignments and approval workflow stage model. A user can decide a stage only when:
- user has `approval_request.decide`;
- current stage is PENDING;
- user has an effective ACTIVE `user_roles` assignment for the stage's `approver_role_id`.

Decisions:
- Approve → next stage or final activation.
- Return → policy becomes editable Draft.
- Reject → policy becomes editable Draft.

## Final approval

Final approval sets:
- `lifecycle_status = ACTIVE`
- `approval_status = APPROVED`
- `is_current_version = true`

Approved policy is read-only.

## Amendment

Only current ACTIVE + APPROVED policy can be amended.

`Amend Policy`:
- creates child `parent_policy_id = source.id`;
- creates next version automatically if version is blank;
- copies Credit / Completion;
- copies dynamic Credit Category Requirements;
- copies Attendance;
- copies Assessment / Examination;
- copies Grading + Grade Bands;
- copies Promotion / Progression Rule Sets + source-term mappings;
- clears validation checkpoint;
- starts `DRAFT / NOT_SUBMITTED`.

The source remains active/current while amendment is Draft or Under Approval.

On amendment final approval:
- amendment becomes ACTIVE / APPROVED / Current;
- source remains historical ACTIVE/APPROVED but `is_current_version = false`;
- source `superseded_by_id` points to the approved amendment.

Historical operational references must continue pointing to the exact policy version originally applied.
