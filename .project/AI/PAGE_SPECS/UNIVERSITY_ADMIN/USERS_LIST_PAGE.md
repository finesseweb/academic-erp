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

## 2026-09-02 Integrity Update
- Internal Access Management excludes `APPLICANT` identities; applicants remain in Admission workflows.
- College Staff rows expose `primary_college_id` as a visible College / Institute name and code.
- College filter is server-side and applies to the College ownership field.
- University visibility includes subordinate College staff; visibility is based on institutional ownership/scope rather than `created_by`.
- Email uniqueness is global and normalized before validation/persistence.
