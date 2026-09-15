# Changelog Patch — Admission Form Assigned Manager College Scope

## 2026-08-27
- Fixed College Admission Form Setup Assigned Manager dropdown leaking University/global/other-College users.
- Manager candidates now come from active `user_roles` assignments in the current College scope.
- Added effective-date and active-role checks.
- Added server-side create/update validation against the same College-scope rule.
- Added College role names to manager dropdown labels for clearer selection.
- No database migration required.
