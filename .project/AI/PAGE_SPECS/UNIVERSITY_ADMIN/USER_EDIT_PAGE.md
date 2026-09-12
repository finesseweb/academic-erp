# Edit User Page

Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW


## Identity
- Module: User Management
- Route: `/super-admin/users/:id/edit`
- Permissions: `user.view`, `user.update`
- Delivery phase: SA-03

## Purpose
Update account-level identity and status-safe fields without mixing business profile data into the account entity.

## UI
Account details, status, role summary, last login/security metadata, audit shortcut.

## API
- `GET /admin/users/:id`
- `PATCH /admin/users/:id`

## Safety
Protected system accounts cannot be accidentally disabled by unsafe workflows. Sensitive fields require explicit permission and validation.

## Audit
`USER_UPDATED` with appropriate before/after values.
