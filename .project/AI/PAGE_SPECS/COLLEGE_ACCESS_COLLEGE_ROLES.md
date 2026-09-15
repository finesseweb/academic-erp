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
- Creator provenance remains separate from ownership: a University-created-on-behalf College role and a College-created role are both College-owned, while `created_by_scope_type` records who created them.
- Supports scoped list, create, edit, permission management, and enable/disable with confirmation and transactional audit.
- College role list presentation follows the same Access & Security visual contract as University Roles where the College context permits it.

## List UI Contract
- Header shows College code, Access & Security context, page title, description, and Create Role action when authorized.
- Summary cards show Total Roles, Active Roles, and Inactive Roles for the current College.
- List supports search by role name/code/description and Active/Inactive status filtering.
- Role table shows Role, Description, Permission count, Assigned User count, Status, and labeled Actions.
- Edit, Permissions, Activate and Deactivate actions remain capability-gated by backend-provided `can` flags.
- Pagination and empty/filter states use the same pattern as University Role Management.
- Creation may be presented in a modal/dialog, but validation and persistence remain server-authoritative.

## Change History
- 2026-08-20: Implemented College-owned custom role list/create foundation.
- 2026-09-03: Aligned College Roles presentation with University Roles list UI while preserving College scope and creator-provenance rules.
