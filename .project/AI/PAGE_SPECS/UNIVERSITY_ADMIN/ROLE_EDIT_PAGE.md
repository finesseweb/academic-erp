# Edit Role Page

Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW


## Identity
- Module: Role Management
- Route: `/super-admin/roles/:id/edit`
- Permissions: `role.view`, `role.update`
- Delivery phase: SA-04

## UI
Role metadata, system/custom indicator, assigned-user count, permission count and shortcut to Permission Matrix.

## Rules
System roles have protected fields. Do not allow unsafe changes to `SUPER_ADMIN` identity semantics.

## API
- `GET /admin/roles/:id`
- `PATCH /admin/roles/:id`

## Audit
`ROLE_UPDATED`
