# Super Admin Login Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Page Identity
- Module: Authentication
- Route: `/login`
- Page type: form
- Delivery phase: SA-01

## Purpose
Authenticate all ERP account types through the central identity system. Super Admin uses the same auth foundation as later staff/student/parent portals.

## Access
Public page. Authenticated users should be redirected to their permitted landing page.

## UI
- premium centered authentication card
- ERP/product identity area
- email/username field
- password field with show/hide
- remember-me only if session policy allows
- Forgot Password link
- submit button
- safe validation/error messages
- theme-aware logo/surfaces

## API
- `POST /auth/login`
- `POST /auth/refresh`
- `GET /auth/me`
- `POST /auth/logout`

The complete request/response and session contract is owned by `PAGE_SPECS/AUTH/LOGIN_PAGE.md`.

## Database Foundation
- Identity source: `users`.
- Effective authorization path: `users -> user_roles -> roles -> role_permissions -> permissions`.
- Refresh/session state: `user_sessions`, storing token hashes only.
- Login security events: append-only `audit_logs`.
- Relevant specs: `users.md`, `user_roles.md`, `roles.md`, `role_permissions.md`, `permissions.md`, `user_sessions.md`, `audit_logs.md`.
- Login reads one user by unique normalized email/username, then evaluates active/effective scoped assignments; no WebSocket is involved.

## Security
- rate limiting
- generic invalid-credential response
- inactive/locked user handling
- secure cookie/token strategy per SECURITY_RULES
- audit login success/failure according to policy
- five invalid password attempts produce a 15-minute temporary lock; public responses remain generic

## Realtime
Not required.

## Theme
Must support all built-in and custom themes. Login must remain readable in both light and dark modes.
