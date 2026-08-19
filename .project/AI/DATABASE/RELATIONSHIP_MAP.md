# Database Relationship Map

## Purpose
This file is the compact join map for application queries and reporting. It must describe verified relationships only.

## Rules
- Never infer a relationship only because two columns share a similar name.
- Record the physical FK when one exists.
- If a legacy logical relationship exists without an FK, mark it explicitly as `logical/unconstrained` and document evidence.
- Include the tenant ownership path so queries cannot accidentally cross affiliated Colleges.

## Relationship Registry

| From Table.Column | To Table.Column | Cardinality | Required? | FK Enforced? | Delete Rule | Business Meaning |
|---|---|---|---|---|---|---|
| `role_permissions.role_id` | `roles.id` | many-to-one | Yes | Yes | RESTRICT | Permission membership of a role |
| `role_permissions.permission_id` | `permissions.id` | many-to-one | Yes | Yes | RESTRICT | Permission granted through a role |
| `user_roles.user_id` | `users.id` | many-to-one | Yes | Yes | RESTRICT | User receiving a scoped role assignment |
| `user_roles.role_id` | `roles.id` | many-to-one | Yes | Yes | RESTRICT | Role assigned to a user |
| `user_sessions.user_id` | `users.id` | many-to-one | Yes | Yes | RESTRICT | Authentication session owner |
| `audit_logs.actor_user_id` | `users.id` | many-to-one | No | Yes | SET NULL | Optional actor; audit survives account removal |
| `user_theme_preferences.user_id` | `users.id` | one-to-one | Yes | Yes | CASCADE | Optional personal theme selection owned by a user |
| `colleges.university_id` | `universities.id` | many-to-one | Yes | Yes | RESTRICT | College affiliated with the University root |
| `colleges.principal_user_id` | `users.id` | many-to-one | No | Yes | SET NULL | Optional linked Principal/College Admin account |
| `authorized_signatories.university_id` | `universities.id` | many-to-one | Yes | Yes | RESTRICT | Governed signing appointment owned by the University |

## Common Join Paths
Document frequently used, verified business join paths here as modules are migrated.

Example format only:
`student -> enrollment -> course_offering -> course`

For each real path added, specify:
- Physical tables and join columns.
- Tenant filters required.
- Effective session/batch/status filters.
- Whether the path represents current state or historical state.

Verified authorization path:
`users -> user_roles -> roles -> role_permissions -> permissions`

- Join on the foreign keys registered above.
- Filter active users, active/effective user-role assignments, active roles and active permissions.
- Evaluate each `user_roles.scope_type + scope_reference` independently; never combine a permission from one assignment with another assignment's scope.
- Current effective access grain is one user + permission + assignment scope.

Verified authentication path:
`users -> user_sessions`

- Sessions contain only a token hash, never the raw refresh/session token.
- Require active user, active session and `expires_at` in the future.

## Reporting Grain
For important fact/transaction tables, record their grain, e.g. "one row per student per class meeting". The grain prevents double counting in reports.

- `role_permissions`: one row per role-permission grant.
- `user_roles`: one row per user-role-scope assignment.
- `user_sessions`: one row per issued refresh/session token.
- `audit_logs`: one immutable row per audit event.
- `theme_policies`: one row per canonical authorization/theme scope.
- `user_theme_preferences`: zero or one row per user.
- `colleges`: one row per affiliated College under the root University.
- `authorized_signatories`: one row per University signatory appointment and authority category.

Verified theme resolution path:
`users -> user_theme_preferences` plus applicable `theme_policies`

- A preference is effective only when the policy permits personal selection and RBAC grants `theme.select_own`.
- Current runtime resolves the `GLOBAL/global` policy. Future College resolution must validate Affiliated College ownership before consulting an College-scoped policy.
