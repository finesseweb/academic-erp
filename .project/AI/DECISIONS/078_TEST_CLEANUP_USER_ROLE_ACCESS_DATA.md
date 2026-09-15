# ADR 078 — Test Cleanup for User and Role Access Data

## Status
Accepted — 2026-09-02

## Context
Access-management testing creates University staff users, College staff users, user-role assignments, and custom University/College roles. The existing Test Data Cleanup Center intentionally preserved all access data, which made repeated access-management QA difficult.

## Decision
The Test Data Cleanup Center will expose **Users** and **Roles** as dependency-aware cleanup categories and will also provide one dedicated **Full User & Role Test Reset** action.

### Individual User cleanup
- Only internal `UNIVERSITY_STAFF` and `COLLEGE_STAFF` identities in the current University hierarchy are listed.
- Applicant identities are excluded and preserved.
- The currently authenticated cleanup user is protected.
- Any user holding the protected `SUPER_ADMIN` role is protected.
- A user is blocked when a RESTRICT operational reference exists, currently including approval submissions and admission interview evaluator rows.
- Safe access-only children such as `user_roles`, passkeys and sessions are removed before the user row.
- Confirmation requires the exact user email shown by the cleanup page.

### Individual Role cleanup
- University/global roles and roles owned by Colleges under the current University are listed.
- System roles are always protected.
- Custom roles are blocked when used by approval workflow stages or approval request stages.
- Safe access-only children (`user_roles`, `role_permissions`, admission-form access-role mappings) are removed before the custom role.
- Confirmation requires the exact role code.

### Full User & Role Test Reset
- Confirmation phrase: `RESET-ACCESS-TEST-DATA`.
- Deletes every currently cleanable internal staff test user first, then recomputes and deletes cleanable custom roles.
- Preserves current cleanup actor, SUPER_ADMIN identities, applicants, system roles, permissions, audit logs and operationally referenced users/roles.
- This is separate from **Full Academic Test Reset**; the academic reset continues to preserve access data.

## Safety
The environment guard and `test_data_cleanup.manage` authorization remain mandatory. Foreign-key checks are not disabled and raw `TRUNCATE` is not used. Cleanup remains dependency-aware and transaction-safe.

## Audit Events
- `TEST_ACCESS_USER_CLEANED`
- `TEST_ACCESS_ROLE_CLEANED`
- `TEST_ACCESS_DATA_FULL_RESET`

## Migration
No database migration is required.
