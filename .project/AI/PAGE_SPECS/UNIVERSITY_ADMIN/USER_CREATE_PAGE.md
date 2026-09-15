# Create User Page

Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW


## Identity
- Module: User Management
- Route: `/super-admin/users/create`
- Permission: `user.create`
- Delivery phase: SA-03

## Purpose
Create a platform account. Business profiles such as Student/Parent/Faculty remain separate domain entities and may be linked later.

## Fields
- full name
- email
- mobile optional according to policy
- username if supported
- account status
- initial authentication method/password policy
- optional initial role assignment
- optional College/scope assignment where applicable

## Rules
Email/username uniqueness is backend-enforced. Password is never stored in plain text. Do not mix student/parent academic profile columns into `users`.

## API
- `POST /admin/users`
- optional role assignment performed atomically or through the role-assignment endpoint according to service design

## Audit
`USER_CREATED`; initial role assignment events.
