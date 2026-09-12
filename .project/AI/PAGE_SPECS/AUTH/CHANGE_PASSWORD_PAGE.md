# Change Password Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Page Identity
- Module: Authentication / Account Security
- Route: `/admin/change-password`
- Page type: configuration
- Delivery phase: Account Security and Theme Access

## Purpose
Allow an authenticated user to replace their own password securely.

## Roles / Permissions
- Required permission: `password.change_own`
- Scope: own record
- Backend enforcement is mandatory.

## Layout / Workflow
- Uses the authenticated application shell and shared page header.
- Requires current password, new password and confirmation.
- New password must contain at least 12 characters and differ from the current password.
- On success, the current session remains active and every other active session for the user is revoked.
- Displays accessible loading, validation, server-error and success states.

## API
- `POST /api/v1/auth/change-password`
- Request: `{ currentPassword, newPassword }`
- Response: `{ changed: true, otherSessionsRevoked: true }`
- `401` for an incorrect current password; `403` when permission is absent; `400` for validation or same-password rejection.

## Database / Audit
- Reuses `users`, `user_sessions` and `audit_logs`; no password-history table.
- Updates `users.password_hash` and `password_changed_at` transactionally with other-session revocation and `PASSWORD_CHANGED` audit creation.
- Passwords and hashes are never written to audit metadata.

## Realtime Decision
REST only. No WebSocket behavior.

## Tests / Definition of Done
- Password hashing/verification tests pass.
- Wrong current password, insufficient permission and weak/same password are denied.
- Other sessions are revoked while the current session remains active.
- All built-in themes and validated custom semantic tokens are supported.

## Change History
- 2026-08-13: Implemented authenticated self-service password change.
