# Change Log

## 2026-08-25 — Academic Policy Degree Level ENUM database fix

- Fixed MySQL `academic_policies.scope_type` so the physical ENUM now includes `DEGREE_LEVEL`.
- Added a follow-up migration rather than editing the earlier migration, so environments where `degree_level_id` was already migrated can be upgraded safely.
- Documented that Academic Policy scope changes must keep request validation, service rules, foreign keys, and the physical database ENUM synchronized.

## 2026-08-25 — Academic Policy Degree Level scope

- Added `DEGREE_LEVEL` as a first-class Academic Policy scope between University-wide and Program Template.
- Added nullable `academic_policies.degree_level_id` referencing the existing Degree Level Master.
- Added backend scope validation so Degree Level is required only for Degree-Level policies and conflicting scope references are rejected.
- Added active Degree Level loading and Degree Level selector/display to the Academic Policy page.
- Added Degree Level to policy clone/amendment inheritance and validation fingerprinting.
- Updated Academic Policy table/page/schema/relationship documentation to keep code and `.project` synchronized.


## 2026-08-24 — Academic Policy amendment lineage text

- Added `Amendment of vX` below amended Academic Policy versions.
- Kept Current/Previous badges unchanged.
- Version lineage presentation now matches Curriculum.

## 2026-08-25 — AI project-context access and consistency rule

- Added a mandatory rule for full-access agents to inspect and follow current repository/project references before development.
- Added a mandatory rule for partial-access AIs to request the specific missing project reference instead of guessing.
- Explicitly applies consistency checks across UI/design, database, backend, frontend, routes, permissions/security, naming, workflows, audit behavior, testing and living documentation.

## 2026-08-25 — Common dynamic Academic Approval engine rule and policy workflow integrity fix

- Fixed Academic Policy submission so the backend accepts only ACTIVE workflows from the same University with `applies_to = ACADEMIC_POLICY`.
- Documented Academic Approval as the single reusable approval engine for future approval-enabled modules.
- Froze the separation between common workflow mechanics and module-specific validation/final lifecycle actions.
- Added future expansion guidance to prefer subject handlers/registration over duplicated approval systems or growing controller branching.
- Updated implementation state, roadmap, registry and Approval QA checklist.
