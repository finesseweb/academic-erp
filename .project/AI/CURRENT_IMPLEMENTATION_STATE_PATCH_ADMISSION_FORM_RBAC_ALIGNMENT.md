# Stage 1 Patch — Admission Form Access Aligned to Existing RBAC

Date: 2026-08-27
Status: IMPLEMENTED_IN_PACKAGE / OWNER_QA_REQUIRED

This patch supersedes the temporary University feature-gate and per-College role-checkbox patches.

Authoritative access model:
`Permission -> Role -> scoped UserRole -> User -> College`

- University/SUPER_ADMIN receives Admission Form permissions by default.
- Admission Form permissions live in the common Permission catalog.
- Any custom role may be assigned them through Access Management → Roles → Permissions.
- College users require the permission through an active role assignment scoped to that College.
- Menu visibility and backend authorization resolve automatically from the same RBAC state.
- No Admission-specific College enable switch or role allow-list remains authoritative.
