# Users List Page

Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW


## Page Identity
- Module: User Management
- Route: `/super-admin/users`
- Page type: list
- Delivery phase: SA-03

## Purpose
Search, filter and administer ERP login accounts.

## Permissions
- page: `user.view`
- create CTA: `user.create`
- edit: `user.update`
- enable: `user.enable`
- disable: `user.disable`
- role assignment: `role.assign`

## UI
Columns: Name, Email/Username, Account Type, University/College Scope summary, Roles, Status, Last Login, Created At, Actions.
Filters: search, role, status, College, account type.
Server-side pagination and sorting required.

## Actions
Create User, View/Edit, Assign Roles, Enable/Disable, Reset Password when permitted.

## API
- `GET /admin/users`
- `PATCH /admin/users/:id/status`

## Audit
Status changes, role changes and administrative password reset actions must be audited.

## Realtime
Not required.
