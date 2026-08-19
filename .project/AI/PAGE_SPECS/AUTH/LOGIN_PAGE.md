# Login Page Specification

Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW


## Page Identity
- Module: Authentication
- Route: `/login`
- Page type: public form
- Delivery phase: SA-01

## Purpose and Access
- Authenticate Super Admin now and future University/College staff, student and parent accounts through one identity foundation.
- Authenticated active users are not asked to log in again; future landing-page routing is based on server-returned effective access.
- Realtime is not required.

## UI / Theme
- Premium responsive split layout on desktop and focused authentication card on small screens.
- Visible identifier/password labels, show/hide password, inline validation, safe server error, loading/disabled submit state and accessible focus styles.
- Uses shared semantic tokens and supports Premium Light, Premium Dark, Ocean Blue, Emerald and validated custom token sets.
- Forgot Password may be displayed as unavailable until its separately specified milestone is implemented.

## API Contract
- `POST /api/v1/auth/login`: `{ identifier, password }`; returns `{ accessToken, expiresIn, user }` inside the global success envelope and sets the refresh cookie.
- `POST /api/v1/auth/refresh`: no body; rotates the HttpOnly refresh cookie and returns a new access token/user.
- `GET /api/v1/auth/me`: bearer access token required; returns the current active user with effective role/permission/scope assignments.
- `POST /api/v1/auth/logout`: revokes the refresh session when present and clears the refresh cookie.
- Authentication failures return `401` with a generic safe message; validation returns `400`; rate limits return `429`.

## Security / Session Contract
- Passwords are verified against versioned salted scrypt hashes using constant-time comparison.
- Access JWT is short-lived and held in frontend memory, not localStorage/sessionStorage.
- Refresh token is opaque random data stored only in an HttpOnly, SameSite=Lax, path-limited cookie; production requires Secure.
- Database stores only SHA-256 refresh-token hashes. Refresh rotates the token and rejects expired/revoked/inactive-user sessions.
- Backend re-evaluates active user, assignment, role and permission state; frontend access visibility is UX only.
- Five failed passwords lock an active account for 15 minutes. Inactive/locked/invalid credentials receive the same public error.
- Login success/failure, refresh rejection, session refresh and logout are audited without storing credentials or tokens.

## Data Access
- Identity lookup: unique normalized `users.email` or `users.username`.
- Authorization path: `users -> user_roles -> roles -> role_permissions -> permissions` at one user/assignment scope grain.
- Session source: `user_sessions`; audit destination: `audit_logs`.
- Relevant specs: `users.md`, `user_sessions.md`, `user_roles.md`, `roles.md`, `role_permissions.md`, `permissions.md`, `audit_logs.md`.

## Tests
- Successful login, incorrect credentials, inactive user, temporary lock, malformed input and rate limit.
- Authenticated `/me`; invalid/expired JWT/session denied.
- Refresh rotates token; old token denied; logout revokes session and clears cookie.
- Audit events contain no password/token data.
- Responsive UI, keyboard labels/focus, loading/error states and all built-in theme tokens.

## Change History
- 2026-08-13: Expanded for SA-01 authentication implementation.
- 2026-08-18: Replaced starter-kit presentation with the shared premium Academic ERP authentication shell, four-theme support, responsive split/mobile layout, themed status feedback, accessible invalid states, passkey presentation, and pending duplicate-submit protection. Existing Fortify/Inertia behavior was not changed.
