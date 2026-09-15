# College User Role Assignment
Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW

## Identity
- Route: `/college/:college/users/:user/roles`
- Permissions: `college_user.view`, `college_role.view`, `college_role.assign`, `college_role.unassign`

## Rules
- Target user must have `primary_college_id` equal to the route College.
- Only active, non-system roles owned by that same College may be assigned.
- Laravel creates the canonical `COLLEGE + college:<id>` scope; clients cannot submit scope identifiers.
- Duplicate assignments, cross-College roles/users, and self-assignment changes are rejected.
- Assign/remove operations are transactional and audited as `USER_ROLE_ASSIGNED` / `USER_ROLE_UNASSIGNED`.
- UI provides role selection, effective permission preview, confirmation, pending states and empty state.

## Change History
- 2026-08-20: Implemented same-College user-role assignment and removal.
