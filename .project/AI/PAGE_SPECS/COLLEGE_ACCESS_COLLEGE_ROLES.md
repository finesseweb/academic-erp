# College Roles
Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW

## Identity
- Route: `/college/:college/roles`
- Permissions: `college_role.view/create/update/disable`, always at the requested College scope

## Rules
- College Admin sees only roles with `owner_scope_type=COLLEGE` and `owner_scope_reference=college:<authorized-id>`.
- New custom roles receive College ownership exclusively from Laravel; cross-College creation is forbidden.
- Supports scoped list, create, edit and enable/disable with confirmation and transactional audit.
- Role-permission delegation is intentionally deferred to the next ordered College Role Permissions milestone.

## Change History
- 2026-08-20: Implemented College-owned custom role list/create foundation.
