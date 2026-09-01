# Authorization and Security Audit Logging

## Purpose
Preserve accountability for authorization changes and sensitive ERP actions.

## Events That Must Be Considered for Audit
### Authentication lifecycle
- `LOGIN_SUCCEEDED`
- `LOGIN_FAILED`
- `SESSION_REFRESHED`
- `SESSION_REFRESH_FAILED`
- `LOGOUT_SUCCEEDED`
- `USER_ROLE_SCOPE_UPDATED`
- `PASSWORD_CHANGED`

Authentication audit metadata contains only safe reason categories and request context; credentials, access tokens, refresh tokens and token hashes are prohibited.

### Security administration
- User created/enabled/disabled
- User account identity updated and administrative password reset initiated (`USER_CREATED`, `USER_UPDATED`, `USER_ENABLED`, `USER_DISABLED`, `USER_PASSWORD_RESET_INITIATED`)
- Role created/updated/disabled
- Role lifecycle events use `ROLE_CREATED`, `ROLE_UPDATED`, and `ROLE_STATUS_CHANGED`; protected system roles cannot enter this mutation path.
- Role assigned/unassigned
- User-role changes use `USER_ROLE_ASSIGNED` and `USER_ROLE_UNASSIGNED` with role code and explicit canonical scope.
- Permission added to/removed from role
- Role permission matrix changes use `ROLE_PERMISSIONS_UPDATED` with added and removed permission codes in one transactional event.
- Password reset initiated/performed by an administrator
- Global theme policy changed (`THEME_GLOBAL_POLICY_CHANGED`)
- User personal theme preference changed (`THEME_PREFERENCE_CHANGED`)

### High-impact business actions
- Fee refund
- Fee discount approval
- Result verification
- Result publication/unpublication if supported
- Sensitive student record changes
- Other approval/reversal operations identified by PAGE_SPECS
- Affiliated College creation, identity updates, and lifecycle status changes (`COLLEGE_CREATED`, `COLLEGE_UPDATED`, `COLLEGE_STATUS_CHANGED`)
- Authorized signatory creation, appointment updates, and lifecycle status changes (`AUTHORIZED_SIGNATORY_CREATED`, `AUTHORIZED_SIGNATORY_UPDATED`, `AUTHORIZED_SIGNATORY_STATUS_CHANGED`)
- Academic session creation, updates, lifecycle and current selection (`ACADEMIC_SESSION_CREATED`, `ACADEMIC_SESSION_UPDATED`, `ACADEMIC_SESSION_STATUS_CHANGED`, `ACADEMIC_SESSION_CURRENT_CHANGED`)
- Degree level creation, updates and lifecycle (`DEGREE_LEVEL_CREATED`, `DEGREE_LEVEL_UPDATED`, `DEGREE_LEVEL_STATUS_CHANGED`)
- Degree, discipline/specialization, program-template and course-category creation, update and lifecycle (`DEGREE_*`, `DISCIPLINE_*`, `PROGRAM_TEMPLATE_*`, `COURSE_CATEGORY_*`)

## Recommended Audit Fields
Reconcile with existing audit/history tables before creating a new table.

- event ID
- actor user ID
- actor role/authorization context when useful
- college/tenant ID
- action/event code
- resource type
- resource ID
- request/correlation ID where available
- safe before/after or change metadata where appropriate
- IP address/user-agent if approved and required
- timestamp

## Rules
- Audit history must not be silently deleted as part of normal CRUD.
- Never store passwords, JWTs, reset tokens, secrets or full sensitive payloads in audit logs.
- Audit writes for critical state changes should participate in the same transaction when practical so business state and audit history cannot diverge.
- Define retention and access permissions before exposing audit reports.
- Audit records themselves require tenant-aware access control.
- The implemented `audit_logs` table is append-only and intentionally has no `updated_at`; an audit event is never edited in place.
- `actor_user_id` is nullable with `ON DELETE SET NULL` so audit history survives account removal.
- Scope fields must both be NULL or both be present, enforced by a database check constraint.

### Test Data Cleanup maintenance events
- Testing-only Admission Form Template lifecycle recovery uses `TEST_ADMISSION_FORM_TEMPLATE_DEACTIVATED`.
- The event is emitted only through the guarded Test Data Cleanup path and records safe before/after template state plus the count of public mappings disabled by the maintenance action.
