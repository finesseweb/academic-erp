# Current Implementation State Patch — Admission Form Assigned Manager College Scope

Status: IMPLEMENTED / QA REQUIRED

## Purpose
College Admission Form Setup must follow the ERP's existing scoped RBAC model. The Assigned Manager selector is responsibility metadata, but a College-owned form must not assign a University user or a user from another College.

## Implemented behavior
- University Admission Form Setup continues to select only eligible University-level managers.
- College Admission Form Setup now lists only ACTIVE users who have at least one ACTIVE role assignment in the current College scope (`scope_type = COLLEGE`, `scope_reference = college:{id}`), within its effective date window.
- Users from other Colleges and University-only users are excluded from the College Assigned Manager dropdown.
- The dropdown displays the user's active role name(s) for that College alongside name/email.
- Create and update endpoints repeat the same scope validation server-side; a crafted request cannot assign an out-of-scope manager.
- No new role system or parallel manager permission system was introduced.

## QA checkpoint
1. Login as College A and open New College Extension.
2. Assigned Manager must show only users with active College A role assignments.
3. College B users and University-only users must not appear.
4. Expired/inactive College A role assignments must not appear.
5. Editing a College template must apply the same list and validation.
6. Directly posting an out-of-scope user id must return validation error.
