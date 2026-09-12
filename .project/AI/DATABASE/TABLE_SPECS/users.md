# Table Specification: Users

## Identity
- Physical table / Laravel migrations / Eloquent model: `users` / `User`
- Domain and scope: Authentication; global identity account
- Purpose and grain: one row per ERP login account; business profiles such as Student/Parent remain separate future entities
- Expected growth: moderate

## Keys and Columns
- PK: unsigned BIGINT `id`.
- Unique: normalized `email`; username login is not enabled in the current Laravel/Fortify implementation.
- Identity/security: `name`, `email`, optional `mobile`, `account_type`, salted encoded `password`, `status`, email verification and optional `last_login_at`.
- Creation provenance: nullable `created_by_user_id` plus immutable `created_by_scope_type` (`UNIVERSITY`, `COLLEGE`, or `APPLICANT`) records which administration hierarchy created the identity. This is distinct from current ownership in `primary_college_id`.
- Audit: `created_at`, `updated_at`.

## Relationships
- Parent of `user_roles`, `user_sessions`, optional `user_theme_preferences`, optional `audit_logs.actor_user_id`, and nullable self-referencing `users.created_by_user_id`.
- Deletes are restricted by roles/sessions; audit actor uses SET NULL.

## Indexes / Access
- Unique email and username support login lookup.
- `(status, created_at)` supports active/status-filtered administration lists.
- `(created_by_scope_type, primary_college_id, status)` supports University-vs-College administration visibility without broad tenant reads.
- Never select or expose `password_hash` outside credential verification.

## History / Security
- Disable/lock through status; do not hard-delete accounts with history.
- Password hashes use an encoded algorithm + salt + derived hash; plaintext is never stored.
- Authentication implements five-attempt temporary lockout for 15 minutes and resets the counter after a successful login.

## Change History
- 2026-08-20: Added nullable `primary_college_id` ownership for College Staff and College/status/name access index.
- 2026-08-13: Created by Core Identity/RBAC foundation.
- 2026-08-13: Authentication service implemented active/disabled/locked handling.
- 2026-08-13: Added optional one-to-one user theme preference relationship.
- 2026-08-19: Added administrative account type, mobile, lifecycle status, last-login field and status/account-type/created-date index.

## 2026-09-02 Access Integrity Clarification
- `primary_college_id` is the authoritative College ownership link for a College Staff identity and is used by both University visibility and College isolation.
- Internal Access Management must not treat Applicant identities as staff accounts.
- Email uniqueness is global. All internal create/update entry points normalize email with trim + lowercase before uniqueness validation and persistence.

## 2026-09-03 Creation-Origin Visibility
- University Access Management may list/control only internal identities whose `created_by_scope_type = UNIVERSITY`, including University-created identities later assigned to a College.
- College Access Management remains ownership-scoped by `primary_college_id = current College` and therefore sees both University-created-on-behalf and College-created staff for that College.
- College-created staff (`created_by_scope_type = COLLEGE`) must not appear in University user administration.
- `created_by_user_id` is provenance; `primary_college_id` remains the current College ownership link.
