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
