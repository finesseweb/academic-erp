# Change Log

## 2026-08-26 — Merit / Selection Rule responsive dialog consistency fix

- Corrected Merit / Roster / Selection Rule create/edit dialog width and viewport overflow.
- Added a bounded scrollable form body with visible header/footer actions.
- Constrained long seat-bucket Select labels/dropdowns and form controls to their container.
- Added the canonical responsive dialog/form contract to `UI_UX_GUIDELINES.md`.
- Added the Merit / Roster / Selection Rules PAGE_SPEC so future changes preserve the same UI and optional-Reservation behavior.

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


## 2026-08-26 — Merit / Roster / Selection Rules milestone
- Implemented the documented layer immediately after Reservation / Seat Distribution.
- Added versioned `college_admission_selection_rules` attached to exact Reservation Plans/effective admission seat buckets.
- Added College-scoped RBAC permissions, controller, requests, service, audit events, routes and React/Inertia UI.
- Added MERIT / ENTRANCE / COMBINED scoring validation, minimum qualifying score, policy/roster reference and tie-break instructions.
- Activation requires ACTIVE Offering + Intake + Reservation Plan and retires any older active rule version for the same seat bucket.
- Dependency-safe Full Academic Test Reset now removes Selection Rules before Reservation Plans.
- Status: IMPLEMENTED / OWNER_QA_REQUIRED before Student Admission begins.

## 2026-08-26 — Selection Rule optional-reservation correction
- Corrected Selection eligibility so Reservation / Seat Distribution is optional per effective Intake seat bucket.
- Buckets with no Reservation use their full capacity as Open/General.
- If Reservation is defined, it must be ACTIVE; an INACTIVE plan blocks only that exact bucket.
- Added explicit Reservation ACTIVE/INACTIVE lifecycle visibility and protected-role permission repair.
- Added dependency guards between ACTIVE Reservation Plans and ACTIVE Selection Rules.


## 2026-08-26 — Structured Selection Rule tie-breakers and thresholds
- Replaced the ambiguous single qualifying-score UI with mode-aware normalized Merit, Entrance and Final Weighted thresholds.
- Added `college_admission_selection_rule_tiebreakers` for ordered machine-readable tie-break criteria.
- Added add/remove/reorder controls and criterion-specific preference labels in the Selection Rule dialog.
- Relevant Subject Score now carries an explicit subject/field reference.
- INACTIVE drafts may be incomplete, but activation requires at least one structured tie-breaker.
- Preserved free-text tie-break wording only as optional policy notes; future merit ranking must not parse it.
- Added ADR 019 and synchronized Selection Rule PAGE_SPEC, TABLE_SPEC, schema catalog, relationship map and implementation state.

## 2026-08-26 — Reservation Plan action/lifecycle UI correction
- Added explicit plan-level Edit plus Activate/Deactivate controls in the Reservation Plan card header.
- Kept quota-row Edit/Delete actions separate from Reservation Plan actions.
- Added backend plan-note update route/request/service with audit logging; seat-bucket identity remains immutable after creation.
- Synchronized Reservation lifecycle permissions to College roles that already hold `college_reservation.update`, preventing an editable INACTIVE plan from having no lifecycle action.
- Added canonical Reservation / Seat Distribution PAGE_SPEC for future UI/workflow consistency.

## 2026-08-26 - Reservation lifecycle visibility correction
- Fixed Reservation Plan cards where `INACTIVE` status was visible but Activate/Deactivate lifecycle actions were hidden.
- Added Seat-Capacity-style confirmation lifecycle control at plan level.
- `college_reservation.update` now acts as a safe lifecycle-management fallback in both page capability data and status authorization, while explicit enable/disable permissions remain supported.
- Documented that plan lifecycle actions and quota-row actions are separate UI responsibilities.


## 2026-08-26 - Reservation lifecycle confirmation footer fix
- Fixed the Reservation lifecycle confirmation dialog so `Cancel` and `Activate Plan` / `Deactivate Plan` always render and the lifecycle operation can actually be submitted.
- Corrected the Inertia Form render-prop structure that hid the footer action at runtime.
- Standardized Reservation lifecycle buttons to neutral outline styling consistent with Edit and other secondary College Academic Setup actions.
- Added the lifecycle-confirmation UI contract to the Reservation PAGE_SPEC to prevent the same render/styling regression in future modules.

## 2026-08-26 — Shared route-file delta merge correction

- Fixed `routes/web.php` after the Reservation Plan action patch accidentally replaced the newer route state and removed Merit / Roster / Selection Rule routes.
- Restored all `college-admission-selection-rules.*` routes while preserving the new `college-reservations.update` route.
- Added a delivery rule: when a delta milestone modifies a file already changed by a prior milestone, the outgoing file must be based on the latest accumulated project state, not the original baseline.
- Delta packages must never regress routes, sidebar entries, permissions, or other shared files introduced by earlier accepted milestones.

## 2026-08-26 — Searchable Program Offering / Seat Bucket selection
- Replaced the single growing Admission Seat Bucket dropdown in Selection Rule creation with a two-stage searchable operational selector.
- Added searchable Program Offering selection first; current-session offerings remain sorted first while other eligible offerings remain available.
- Admission Seat Bucket is now a second searchable dependent selector containing only buckets from the chosen Program Offering.
- Changing Program Offering clears the previously selected Seat Bucket to prevent cross-offering selection.
- Added `college_program_offering_id` to Selection Rule page bucket payload strictly for UI grouping/filtering; persisted Selection Rule ownership remains the existing Intake + bucket relationship.
- Added reusable UI guidance for searchable parent/dependent operational selectors so future high-volume College Academic modules do not use oversized flat dropdowns.

## 2026-08-26 — Interview selection component
- Added Interview as a first-class normalized 0-100 Selection Rule component alongside Merit and Entrance.
- Added INTERVIEW-only mode and expanded COMBINED mode to support any two or all three positive component weights totaling exactly 100%.
- Added Interview Weight, Minimum Interview Score and Interview Score structured tie-break support.
- Preserved future execution boundary: Interview Scheduling/Evaluation belongs to Student Admission Processing before Merit/Roster generation; Student Lifecycle begins only after Admission Confirmation and does not own selection/interview scoring.
- Added ADR 020 and synchronized Selection Rule PAGE_SPEC, TABLE_SPECs, schema catalog, hierarchy patch, Admission reservation-consumption ADR, QA workflow and implementation state.

## 2026-08-26 — Applications / Candidate Eligibility
- Implemented `college_admission_applications` and ordered `college_admission_application_choices`.
- Added DRAFT/SUBMITTED/WITHDRAWN application lifecycle and per-choice PENDING/ELIGIBLE/INELIGIBLE preliminary eligibility.
- Added searchable Admission Cycle -> Program Offering -> Admission Seat Bucket selection with multiple ordered choices.
- Submission revalidates and locks the exact ACTIVE Selection Rule version, optional ACTIVE Reservation Plan and bucket context; `submitted_at` becomes the authoritative Application Submitted At tie-break value.
- Restored Admission Cycle routes/sidebar and corrected activation to require an eligible ACTIVE Selection Rule rather than mandatory Reservation.
- Added College permissions, audit events, cleanup support and Full Academic Reset dependency ordering.
- Added ADR 021, Application/Choice TABLE_SPECs and Applications PAGE_SPEC; synchronized hierarchy/state/workflow/schema/relationship documentation.

## 2026-08-26 — Admission prerequisite schema hotfix

- Restored the missing `college_admission_cycles` database/model/controller/request/permission foundation required by Admission Applications.
- Added short explicit MySQL FK/index names for Admission Cycle schema.
- Added a repair migration guaranteeing `college_admission_selection_rule_tiebreakers` exists under the documented table name before downstream Admission modules use it.
- Applications retain a real FK to Admission Cycle; relational integrity is not bypassed.
- Migration prerequisite rule: a downstream milestone may not ship a foreign key to a table whose creating migration is absent from the accumulated implementation state.

## 2026-08-26 — Admission Cycle anchored to Program Offering
- Replaced independent Academic Session selection on Admission Cycle with a searchable ACTIVE Program Offering selector.
- Added `college_admission_cycles.college_program_offering_id` with short explicit FK/index names and safe legacy backfill when mapping is unambiguous.
- Program Offering is authoritative; Academic Session is derived and retained only as a compatibility/reporting snapshot.
- Admission Cycle activation now validates ACTIVE Intake + ACTIVE Selection Rule inside that exact offering.
- Applications now inherit Program Offering from Admission Cycle and show only dependent searchable Admission Seat Buckets/specializations; cross-offering choices are rejected server-side.
- Added ADR 022 and updated hierarchy, relationship map, schema/table/page specs and workflow.

## 2026-08-26 — Admission Cycle curriculum version field hotfix

- Fixed Admission Cycle Program Offering eager-loads to use the real `curricula.version` column instead of nonexistent `curricula.version_no`.
- Updated the Admission Cycle frontend type to match `version` as a string field.
- Added a schema-consistency rule: downstream implementation must verify actual migration/table-spec column names before selecting or serializing related model fields.
