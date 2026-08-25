# Change Log

## 2026-08-25 — Reservation / Quota / Seat Distribution implemented

- Added University Reservation / Quota Category Master with Vertical/Horizontal nature.
- Added College Reservation plans against effective Intake admission seat buckets.
- Added derived Open/Unreserved remaining and Horizontal overlay handling.
- Added Intake downstream guards, permissions, audit events, routes, sidebar and UI.
- Added individual Test Data Cleanup and Full Reset dependency order.
- Added Reservation TABLE/PAGE specs, ADR 016 and Implementation Completion Contract.

## 2026-08-25 — Intake changed to hierarchical Discipline/Specialization capacity

- Finalized Program -> Discipline -> optional Specialization seat hierarchy.
- Removed Specialization as a mutually-exclusive Intake mode.
- Added self-parent allocation relationship so Specialization capacity is contained within its Discipline capacity.
- Activation requires Discipline total = Program capacity; Specialization total only needs to be <= parent Discipline capacity.
- Added derived General Discipline remaining seats.
- Froze future Student Lifecycle references: Discipline allocation required, Specialization allocation nullable.
- Added ADR 015 and synchronized Intake PAGE/TABLE specs and hierarchy.

## 2026-08-25 — Intake seat-bearing specialization model corrected

- Replaced ambiguous Program-only/Structured Intake model with PROGRAM / DISCIPLINE / ADMISSION_SPECIALIZATION allocation levels.
- Added explicit `is_admission_seat_bearing` flag to Program Template specialization mappings, default false.
- Optional/curriculum specializations no longer participate in Intake automatically.
- Admission Specialization allocations require explicit seat-bearing configuration.
- Documented separate Admission seat identity vs Student academic specialization/elective choice.

## 2026-08-25 — College Intake / Seat Capacity implemented

- Added one Intake header per active College Program Offering.
- Added Program-only and Discipline/Specialization structured seat-capacity modes.
- Added child allocation table with mapped Discipline/Specialization validation and display order.
- Added activation integrity gate requiring structured allocations to total approved capacity exactly.
- Added protected/delegable permissions for SUPER_ADMIN and COLLEGE_ADMIN.
- Added College-scoped audit events, sidebar/routes/UI and Test Data cleanup dependency updates.
- Added ADR 014 plus TABLE/PAGE specs.

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

