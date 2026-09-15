# ADR 027 — Admission Form Builder CRUD, Optional Panels, and College Administrator Defaults

## Status
Accepted — Stage 1 prerequisite QA patch.

## Decision
Admission Form Setup must follow the ERP's existing hierarchy + RBAC conventions.

1. University base templates support edit and safe delete while DRAFT.
2. Steps support edit/delete while the owning template is DRAFT.
3. A Step may contain zero or more optional Panels/Sections. A Field may belong to a Panel or remain directly under the Step.
4. Panels support edit/delete while DRAFT. Deleting a Panel preserves its Fields by moving them to direct Step placement.
5. Fields support edit/delete while DRAFT. Deletion is blocked if historical application values or dependent field conditions exist.
6. Activated templates are structurally protected. Historical applications remain immutable through the existing form snapshot/data references.
7. `COLLEGE_ADMIN` receives active Admission Form Setup permissions by default, consistent with other College hierarchy modules.
8. College Administrator permission does not override University governance. A College extension remains possible only when the University base template has `allow_college_override = true`.
9. Custom roles remain configurable through Access Management → Roles → Permissions.

## Hierarchy impact
None. This remains inside the documented Stage 1 prerequisite branch. After Stage 1 QA, development returns to Interview QA and then Merit/Roster.
