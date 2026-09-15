# ADR 077 — User Visibility, Institutional Scope and Duplicate Identity Protection

Status: ACCEPTED
Date: 2026-09-02

## Context
University Access Management must be able to identify which affiliated College owns a College staff account. College Access Management must show all staff owned by that College, including its College Administrator, together with their effective College-scoped roles. Applicant identities must not be mixed into internal staff Access Management. The `users` table is a global login identity store and email is the unique login key.

## Decision
1. University Access Management lists internal ERP users created through the University hierarchy and excludes both `APPLICANT` identities and College-origin staff identities. A University-created identity remains University-manageable when it is later assigned to an affiliated College.
2. `users.primary_college_id` identifies current College ownership. `users.created_by_scope_type` independently records creation origin. University visibility requires `created_by_scope_type = UNIVERSITY`; College visibility requires `primary_college_id = current College`.
3. College Access Management is strictly constrained to `account_type = COLLEGE_STAFF` and `primary_college_id = current College`. It must include the College Administrator itself and display active roles assigned in that College scope.
4. A College cannot view or mutate users belonging to another College. Direct URL access must enforce the same ownership/type checks as list queries.
5. Applicant accounts are managed through Admission workflows and cannot be edited, enabled/disabled, password-reset, or role-managed through internal staff Access Management routes.
6. Email is the global user identity key. University and College create/update flows normalize it with trim + lowercase before validation/persistence and reject any existing normalized email. Duplicate identities are therefore blocked across University and all Colleges, not merely inside one College.
7. Creation origin is a visibility rule and is persisted explicitly as `created_by_scope_type`; `created_by_user_id` records the actor. Ownership and creation origin must not be conflated.

## Result
- University can see and control `College A → Admission Operator` only when University administration created that identity.
- A staff identity created by College A remains visible to College A but is excluded from University Access Management.
- College A can see its Administrator and operators with their College roles.
- College B cannot see College A users.
- The same email cannot be used to create a second user from either University or College Access Management.

## 2026-09-03 Amendment
The original blanket University visibility of all College staff is superseded. Creator hierarchy is now part of authorization visibility. Backend direct-resource actions must enforce the same rule as list queries.
