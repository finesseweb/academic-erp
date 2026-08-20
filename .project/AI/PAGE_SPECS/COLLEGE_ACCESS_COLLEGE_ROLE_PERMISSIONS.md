# College Role Permissions
Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW

## Identity
- Route: `/college/:college/roles/:role/permissions`
- Permissions: `college_role.view`, `college_permission.assign`, `college_permission.remove` at the same College scope

## Purpose
Configure a College-owned custom role using only explicitly delegated permissions held by the acting College administrator in that College.

## Security Rules
- Role ownership must be `COLLEGE + college:<route-college-id>` and system roles are rejected.
- Only active permissions marked `is_college_delegable` appear or may be submitted.
- New grants must also exist in the actor's active, effective permission set at the same College.
- Non-delegable grants already controlled by University administration are preserved and cannot be removed through this page.
- University, cross-College, unknown and inactive permissions are denied by Laravel.
- Changes use the shared matrix, confirmation, pending state and transactional `ROLE_PERMISSIONS_UPDATED` audit event.

## Change History
- 2026-08-20: Implemented delegated College permission matrix and anti-escalation validation.
