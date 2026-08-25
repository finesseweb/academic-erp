# Change Log

## 2026-08-25 — Logout redirect standardized

- Overrode Fortify `LogoutResponse` so every successful logout redirects to the named ERP `login` route.
- Removed logout's dependency on the root/home route, preventing the Laravel/Inertia welcome page from appearing after logout.
- Added permanent `AUTH_NAVIGATION_CONTRACT.md` and linked it from the Project Constitution.

## 2026-08-25 — Global UI/data selection consistency contract

- Standardized Current ACTIVE Academic Session as the creation default for session-dependent modules.
- Corrected Curriculum, Academic Policy and Academic Calendar creation forms; College Program Offering already follows the same rule.
- Preserved historical references on Edit/View when Current Session changes.
- Added mandatory project-wide rules for configured `display_order`, dependent dropdowns, status/eligibility filtering, current-vs-historical version selection and frontend/backend validation parity.
- Added `UI_DATA_SELECTION_CONSISTENCY.md` and linked it from Project Constitution, Page Flow Standard and Page Spec Template.

## 2026-08-25 — Program Offering current Session/Curriculum selection correction

- New College Program Offering now preselects the University's current ACTIVE Academic Session.
- Eligible non-current PLANNED/ACTIVE sessions remain selectable.
- Program Offering Curriculum choices now include only the derived Current ACTIVE + APPROVED Curriculum for the selected Program + Academic Session.
- Backend validation rejects superseded/Previous Curriculum versions.
- Preserved ADR 007 design: Curriculum currentness is derived from approved amendment children; no duplicate mutable current flag was added.
- Existing Program Offerings remain historically pinned to their stored Session/Curriculum references.
- Updated Program Offering PAGE_SPEC, ADR 012, ADR 007, curricula TABLE_SPEC and CURRENT_IMPLEMENTATION_STATE.

## 2026-08-25 — College Program Offering protected-role permission synchronization

- Corrected the College Program Offering permission baseline for protected system roles.
- Added a forward corrective migration that synchronizes all five `college_program_offering.*` permissions into both `SUPER_ADMIN` and `COLLEGE_ADMIN`.
- Preserved `is_college_delegable = true` so College Administrators may still delegate permitted capabilities to College-owned custom roles.
- Kept protected system-role permissions read-only in the Role Permission Matrix; no controller/UI security rule was weakened.
- Updated Program Offering PAGE_SPEC, ADR 012, RBAC design and permission catalog.

## 2026-08-25 — College Program Offerings

- Closed University Academic Calendar QA/owner gate.
- Implemented first College Academic Setup module: Program Offerings.
- Added `college_program_offerings` with College + Program Template + Curriculum + Academic Session references and non-destructive lifecycle.
- Added College-delegable permissions and College-scoped audit events.
- Added backend integrity checks for same-University ownership, approved active matching Curriculum, duplicate prevention and inactive-College mutation blocking.
- Added College Program Offerings Inertia/React page and hierarchical sidebar entry.
- Added TABLE_SPEC, PAGE_SPEC and ADR 012.

## 2026-08-25 — Academic Calendar integrity and explicit lifecycle audit

- Added Academic Session -> Academic Calendar date integrity validation: Session date changes are blocked when existing Calendar events would fall outside the proposed new Session range.
- Preserved dependent data explicitly: Calendar events are not silently disabled or deleted.
- Replaced generic Calendar/Event status audit names with explicit `ACTIVATED` / `DEACTIVATED` events.
- Updated Academic Session, Academic Calendar, Calendar Event and ADR 011 documentation.

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
## 2026-08-25 — Academic Calendar foundation
- Marked Common Academic Approval QA complete after project-owner confirmation.
- Added University `academic_calendars` and `academic_calendar_events` design/implementation.
- Enforced one official University calendar per Academic Session and event dates within session bounds.
- Added per-event `allow_college_override` for the future College Academic Calendar milestone.
- Added granular Academic Calendar permissions and Super Admin seed/grants.
- Added audited Laravel service/controller/Form Requests and `resources/js/pages/academic-calendars/index.tsx`.
- Added ADR 011, PAGE_SPEC, TABLE_SPECs, schema/relationship/permission documentation and Calendar QA gate.

