# Roles List Page

Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW


## Identity
- Module: Role Management
- Route: `/super-admin/roles`
- Page type: list
- Permission: `role.view`
- Delivery phase: SA-04

## UI
Columns: Role Name, Code, Type (System/Custom), Permission Count, Assigned Users, Status, Updated At, Actions.
Search and filter by type/status.

## Actions
Create Role, Edit, Configure Permissions, Enable/Disable where permitted. Protected system roles cannot be deleted.

## API
- `GET /admin/roles`
- `PATCH /admin/roles/:id/status`

## Institutional Ownership Visibility — 2026-09-02
- University Role Management must expose the owning institution for every role.
- College-owned custom roles (`owner_scope_type=COLLEGE`, `owner_scope_reference=college:<id>`) display the resolved College name and code.
- University-owned roles display `University`; global protected/system roles display `Global`.
- The list supports filtering by owning College.
- Role ownership is distinct from assignment scope. For example, the protected `COLLEGE_ADMIN` role template is University-owned even when individual users receive that role at a specific College scope.
- Missing/deleted College ownership references must never be silently presented as University-owned; the UI must expose the unresolved canonical scope reference for investigation.
