# Table Specification: User Sessions

## Identity
- Physical table / Laravel migrations / Eloquent model: `user_sessions` / `UserSession`
- Domain/grain: Authentication; one row per issued refresh/session token

## Keys and Columns
- PK unsigned BIGINT; required user FK with RESTRICT delete.
- Unique SHA-256-style fixed-length `token_hash`; raw tokens are never stored.
- Status, expiry, last-use/revocation timestamps, IP/user-agent context and timestamps.
- DB check requires `revoked_at` exactly when status is REVOKED.

## Indexes / Access
- `(user_id, status, expires_at)` supports session validation/revocation by user.
- `(status, expires_at)` supports expiry cleanup.

## History / Security
- Session revocation is state change, not hard deletion during normal security history windows.
- Retention cleanup policy must be approved with authentication implementation.
- Refresh tokens rotate atomically; reuse of the previous hash fails. Logout marks the session REVOKED and records `revoked_at`.
- Access JWT authentication also requires this server session to remain ACTIVE and unexpired.

## Change History
- 2026-08-13: Created by Core Identity/RBAC foundation.
- 2026-08-13: Activated as the refresh/session authority for SA-01 authentication.
