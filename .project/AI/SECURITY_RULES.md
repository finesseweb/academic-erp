# Security Rules

## Authentication
- Use JWT-based authentication for application sessions unless an approved architecture decision replaces it.
- Authentication answers only **who the user is**. It must never be treated as sufficient authorization.
- Never expose secrets, password hashes, tokens, reset tokens, internal authorization metadata, or sensitive configuration.
- Validate and normalize all external input.

### Implemented Session Policy
- Access tokens are short-lived signed JWTs returned in the response and retained by the frontend in memory only.
- Refresh/session tokens are opaque 256-bit random values held in an HttpOnly, SameSite=Lax cookie scoped to `/api/v1/auth`; production cookies are Secure.
- Only SHA-256 refresh-token hashes are stored in `user_sessions`; refresh rotates the token atomically and logout revokes the session.
- JWT validation is followed by active user and active, unexpired server-session validation. Disabling a user or revoking the session therefore blocks protected API access.
- Password verification supports the versioned salted scrypt format created by the identity seed and uses constant-time hash comparison.
- Five failed password attempts temporarily lock an active account for 15 minutes. Invalid identity, password and account state share one generic public error.
- Self-service password changes require `password.change_own`, verification of the current password, a different new password of at least 12 characters, revocation of all other active sessions and a credential-free `PASSWORD_CHANGED` audit event.
- The initial login rate limiter is process-local and suitable for the documented single application server. If horizontal application scaling is introduced, its state must move to an approved shared store.

## Authorization Model
The ERP uses **RBAC with granular permissions and scope-aware authorization**.

Authorization is evaluated as:

`Authenticated User -> Assigned Role(s) -> Permission -> Scope -> Resource/Business Rule`

A role is a reusable collection of permissions. Application code must authorize by permission, not by hard-coded role names, except where a documented system-level rule genuinely requires a specific role class.

Preferred permission format:

`<resource>.<action>`

Examples:
- `student.view`
- `student.create`
- `attendance.mark`
- `fee.collect`
- `fee.refund`
- `result.enter`
- `result.verify`
- `result.publish`
- `role.assign`

The canonical permission list is maintained in `SECURITY/PERMISSION_CATALOG.md`.

## Scope / Tenant Isolation
Permission alone is not enough. Every tenant-sensitive operation must also verify the user's allowed scope.

Supported scope dimensions may include, where relevant:
- Global / all colleges
- University / Affiliated College
- Department
- Academic session/program/batch
- Course / course offering / class
- Student ownership/self-service

A user who has a permission in one college, department, course or other scope must not automatically receive the same access outside that scope.

Never trust `college_id`, `department_id`, `student_id`, `course_id`, or similar scope identifiers supplied by the client without validating them against the authenticated user's authorized scope.

## Backend Enforcement
Laravel is the security authority.

- Controllers declare required permissions.
- Guards/interceptors may perform authentication and coarse permission checks.
- Services must enforce resource-level scope, ownership, assignment, workflow state and business authorization.
- Repositories must receive already-authorized filters/scope and must not broaden access.
- Direct URL/API manipulation must never bypass authorization.

Frontend hiding is UX only and is not a security control.

## Frontend Enforcement
React should use the authenticated user's effective permissions to:
- Hide inaccessible navigation items.
- Hide/disable unauthorized buttons and actions.
- Prevent navigation to inaccessible pages where practical.

The backend must repeat all required authorization checks regardless of frontend behavior.

## Roles
Initial system role concepts may include:
- `SUPER_ADMIN`
- `COLLEGE_ADMIN`
- `DEPARTMENT_ADMIN`
- `FACULTY`
- `ACCOUNTANT`
- `EXAMINATION_CONTROLLER`
- `LIBRARIAN`
- `STUDENT`

These are starting role templates, not a substitute for permissions. Colleges may require custom roles composed from approved permissions.

## Maker-Checker / Approval Workflows
Do not model sensitive workflows with a single broad `manage` permission when separate duties are required.

Examples:
- `result.enter` -> `result.verify` -> `result.publish`
- `fee.discount.request` -> `fee.discount.approve`
- `certificate.generate` -> `certificate.approve`

Where business policy requires separation of duties, the same user must not approve their own action unless explicitly permitted and documented.

## Authorization Audit
Security-sensitive changes and high-impact business actions must be auditable. Examples include:
- Role assignment/removal
- Permission changes
- User enable/disable
- Fee refund/discount approval
- Result verification/publication
- Sensitive student-record changes

Audit records should identify actor, tenant/scope, action, target resource, target ID where applicable, timestamp and relevant before/after metadata without storing secrets.

## Required Security Documentation
Before implementing or changing authorization, read and maintain:
- `SECURITY/RBAC_DESIGN.md`
- `SECURITY/PERMISSION_CATALOG.md`
- `SECURITY/ROLE_MATRIX.md`
- `SECURITY/AUTHORIZATION_FLOW.md`
- `SECURITY/AUDIT_LOGGING.md`
