# ADR 035 — Admission Form RBAC Is the Single Runtime Access Authority

**Status:** Accepted  
**Date:** 2026-08-31  
**Scope:** Stage 1 — Admission Form Configuration / Applicant Entry prerequisite branch

## Decision

The Admission Form module MUST NOT use `college_admission_form_access_controls` or `CollegeAdmissionFormAccessControl` as an operational feature gate.

Admission Form access follows the ERP's standard authorization model only:

`User -> Role -> Permission -> Scope`

- University/global access is decided by effective University permissions.
- College access is decided by the user's College-scoped role/permission assignment.
- University template governance is NOT an access-control system.
- `college_admission_form_templates.allow_college_override` controls only whether a College may create/maintain an extension of that University base template.
- A College with `college_admission_form.map` may map/use an ACTIVE University base template even when structural override is not allowed.

## Superseded runtime pattern

The earlier temporary feature-gate design based on:

`college_admission_form_access_controls`

is superseded and MUST NOT be queried by controllers, middleware, shared Inertia props, sidebar logic, or University/College setup pages.

Historical migrations may remain in migration history. The old model may remain temporarily for source-history compatibility, but no active request path may depend on the dropped table.

## Reason

The parallel feature-gate duplicated RBAC and caused a production/runtime regression after the alignment migration correctly dropped the old table. The project already has a standardized role/permission/scope hierarchy, so Admission Form Setup must behave like the other governed modules.

## Result

- College Admission Form Setup opens using scoped RBAC only.
- University Admission Form Setup no longer renders a separate College Access Governance panel.
- No special College enable/disable route is used.
- College extension capability remains governed by University Base `Allow College Override`.
- Mapping remains an operational College permission and is independent of structural override.
