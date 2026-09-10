
## 2026-09-09 — ADR 169 Academic Period effective-year + occupied-date guard
- Fixed shared DatePicker min/max parsing for ISO datetime boundaries by normalizing them to date-only before year/month calculation.
- Academic Period Year selector now derives only from Academic Session ∩ Curriculum Effective From/To.
- Existing ACTIVE Term Period ranges of the same Curriculum are disabled in Academic Period Start/End Date pickers.
- Added backend same-Curriculum overlap rejection; editing excludes the current Period itself.
- Academic Period UI now shows already allocated Semester/Year ranges and displays effective boundaries as readable dates rather than raw timestamps.
- No migration. QA required before Fee Setup/Late Fine progression.

## 2026-09-09 — ADR 158 — Late Fine / Penalty Rules
- Added College Late Fine Rule master and auditable Late Fine Charge revision history.
- Added FIXED/PERCENTAGE and ONE_TIME/PER_DAY/PER_WEEK calculations, Grace Days and optional cap.
- Added manual and scheduled recalculation; scheduled command is `fees:recalculate-late-fines`.
- Added Current Session-first compact Late Fine Register with server pagination.
- Fee Demand now exposes separate Late Fine and Payable incl. Fine without mutating Gross Demand.
- Integrated late-fine reversal with Fee Demand cancellation, Admission Confirmation revocation and installment schedule replacement.
- Added Late Fine Charges to Test Data Cleanup and Fee Demand downstream dependency checks.
- Added permissions and College sidebar entry.
- QA pending; Payment Collection + Allocation remains next after Late Fine QA PASS.


## 2026-09-09 — ADR 153: Bulk Installment scope MySQL GROUP BY hotfix
- Fixed SQLSTATE 42000 / MySQL error 1055 in Bulk Installment scope loading under `ONLY_FULL_GROUP_BY`.
- Scope discovery now normalizes Fee Demand Item context in an inner query and aggregates by normalized aliases in an outer query.
- No installment eligibility/business-rule change; ADR 152 normalization remains authoritative.
- No migration required.

## 2026-09-04 — Batch parent-protection QA UX fix

- Owner QA confirmed the backend correctly blocks Intake and Program Offering deactivation while an ACTIVE Batch depends on them.
- Intake / Seat Capacity now receives `active_batch_count`; its deactivate action is disabled in the UI when an ACTIVE Batch exists, with hover guidance to deactivate the Batch first.
- Program Offering now receives `active_batch_count`; its deactivate action follows the same disabled-state guidance while ACTIVE Batches exist.
- Backend guards remain authoritative and now return clearer validation messages; status dialogs also render server-side validation errors instead of failing silently.
- No Batch, Intake, Program Offering, seat-capacity, reservation or admission business rule was changed by this QA fix.

# Change Log

## 2026-09-04 — QA refinement: Disable Seat Allocation cancellation after Admission Confirmation

- Seat Allocation screen now receives the linked Admission status for each allocation.
- `Cancel Allocation` is disabled when the linked Admission is `CONFIRMED`, making the protected state visible before the operator attempts the action.
- The disabled button explains that Admission must be revoked first.
- Backend rejection remains in place as defense-in-depth; this change does not weaken or replace the transactional protection.
- A `REVOKED` Admission leaves the allocation active and re-enables explicit cancellation as designed.
- No database schema change.

## 2026-09-04 — QA fix: Seat Allocation cancellation rejection feedback

- Fixed College Seat Allocation cancellation UI so backend validation failures are no longer silent.
- When an ACTIVE seat allocation is protected by a CONFIRMED admission, the page now shows the rejection message to the operator.
- Clarified the backend validation message to instruct the operator to revoke Admission Confirmation first, then cancel the seat allocation.
- Added cancellation processing state to prevent duplicate requests while the PATCH request is running.
- Business rule is unchanged: Admission Confirmation protects the physical seat allocation; this is a QA/UX feedback fix only.


## 2026-09-01 — Testing-only Admission Form Template deactivation

- Added `Deactivate for Testing` under Test Data Cleanup -> Admission Form Templates for ACTIVE test templates.
- The maintenance action returns only the selected template from ACTIVE to DRAFT; it does not delete template structure or Admission Applications.
- Enabled public applicant mappings for that template are disabled automatically before the lifecycle change.
- Reused `test_data_cleanup.manage`, exact template-Code confirmation, and the existing Test Data Cleanup environment guard.
- Added audit event `TEST_ADMISSION_FORM_TEMPLATE_DEACTIVATED` with before/after state.
- Normal Admission Form Setup remains structurally frozen after activation; this does not introduce a production Deactivate lifecycle action.
- No database schema change. Governing decision: ADR 072.

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

## 2026-08-27 — Interview Scheduling / Evaluation
- Implemented interview scheduling, College-user panel assignment, evaluator score normalization, final Interview component and shared Admission Score re-evaluation. See `CHANGELOG_PATCH_ADMISSION_INTERVIEW.md`.

## 2026-08-27 — Admission Form Configuration & Internal Entry Stage 1
Approved prerequisite branch added before Merit/Roster continuation. Added scoped dynamic form builder, manager assignment, dynamic field/file responses, Regular vs Direct admission mode, scoped Application Fee rules with snapshotting, and reuse of existing Admission Application/Application Choice transaction chain. See ADR 024 and `CHANGELOG_PATCH_ADMISSION_FORM_STAGE1.md`.

### 2026-08-27 — Stage 1 Test Data Cleanup coverage
- Added Admission Form Templates and Application Fee Rules to the Test Data Cleanup Center.
- Full Academic Test Reset now removes Stage 1 application field values (through application deletion), form mappings, fee rules, and form templates in dependency-safe order.
- Individual cleanup blocks template/rule deletion when submitted/test Applications still reference them.

## 2026-08-27 — Stage 1 University governance access correction
- Added University Admission Form Setup page and University-owned base-template workflow.
- Added `college_admission_form_access_controls` to explicitly enable/disable Stage 1 Form Setup for each College.
- Added per-College governance mode and independent College fee-override authorization.
- College Admission Form Setup menu now appears only after University enablement and applicable College RBAC permission.
- Backend authorization now enforces the University feature gate.
- Separated University-scope sidebar permission evaluation from aggregated College permissions to prevent cross-scope menu leakage.
- Added access-control records to Full Academic Test Reset coverage.

## 2026-08-31 — Applicant identity and future Student login
- Replaced anonymous mapped-form entry with Applicant Registration/Login gateway.
- Added College-controlled registration, email verification and CAPTCHA settings with RBAC.
- Added `applicant_profiles` and application ownership by `applicant_user_id`.
- Core Name/DOB/Email/Phone are captured once at registration and reused in the application.
- Added Student portal identity transition contract: same login becomes STUDENT only after final Admission Approval/Enrollment creates a Student record.

## 2026-08-31 — Admission Form RBAC runtime regression fix
- Removed operational references to the superseded `college_admission_form_access_controls` feature-gate table.
- College and University Admission Form Setup now use the ERP's standard scoped RBAC model as the single access authority.
- Removed obsolete University College-access route/UI and Inertia shared feature-gate data.
- Preserved University Base `Allow College Override` as structural governance only.
- Added ADR 035.

## 2026-08-31 — Admission Form College Render Recovery
- Fixed blank College Admission Form Setup page caused by missing `curricula` Inertia prop.
- College controller now supplies only current approved ACTIVE curricula after amendment resolution.
- Added defensive frontend collection defaults to avoid white-screen crashes from absent list props.
- Added ADR 036.

## 2026-08-31 — College Admission Form SSR nested-relation safety
- Fixed College Admission Form Setup SSR crash `Cannot read properties of undefined (reading 'map')`.
- Added recursive runtime normalization for template steps, mappings, parent steps, panels, fields, options, conditions and scopes.
- Added safe defaults for top-level selector arrays, fee rules and applicant registration settings.
- Added ADR 037.

- 2026-08-31: Applicant Portal modernization: theme-token based registration/login UI, shared ERP DatePicker for applicant DOB and public dynamic DATE fields, Forgot Password entry point, and real signed applicant email verification/resend flow. See ADR 038.

- 2026-08-31: Fixed Applicant email-verification runtime namespace (`Illuminate\Auth\MustVerifyEmail`) and added mapping-level optional seat/category selection. Seat selection now defaults to Disabled for public application submission. See ADR 039.

## 2026-08-31 — Applicant verification isolation regression fix
- Removed the global `MustVerifyEmail` contract from the shared `User` model.
- Applicant email verification remains available through the existing helper trait and is enforced only inside the Applicant Admission Portal when the College setting requires it.
- Restored College Admin / University / internal ERP login behavior so Applicant Registration settings cannot redirect internal accounts to the generic verification screen.
- Added ADR 040 documenting the authentication boundary.

## 2026-08-31 — Applicant public shell / premium gateway
- Removed internal ERP AppLayout from `applicant/*` pages.
- Redesigned Applicant Gateway as a theme-aware public admissions experience.
- Added mapped College/University/Program/Cycle/Session context to the gateway.
- Preserved shared DatePicker and applicant registration/login behavior.

## 2026-08-31 — Public Applicant Gateway hard isolation + premium refresh
- `/apply/{slug}` now renders the unique `public/admission-gateway` component instead of the legacy `applicant/gateway` entry page.
- Added page-level no-layout boundary and global `public/*` layout exclusion so internal Dashboard/sidebar cannot wrap public admission pages.
- Refreshed gateway visual hierarchy with theme-aware premium admission shell, application context, tabbed new/existing applicant flow, trust indicators, and shared ERP DatePicker.

## 2026-08-31 — Public application academic journey + premium preview
- Added applicant-facing Logout on the live application page.
- Reworked public application styling into a premium theme-token-aware admission journey with step rail/cards.
- Added first-stage Discipline and optional Specialization selection from the mapped Program Offering.
- Added curriculum-driven mandatory Course/Paper auto-allotment and CHOICE-slot selection with min/max validation.
- Added final premium Review & Submit preview with confirmation.
- Public Regular Admission submission is no longer seat-capacity/bucket gated; seat/reservation/allocation remains downstream.
- Added academic-preference/course-choice persistence separate from seat choices.
- Added ADR 043.

- 2026-08-31: Reconciled Test Data Cleanup with Applicant Academic Preference/Course Choice and registration-setting data. Individual Admission Application cleanup and Full Academic Reset now delete new application children in FK-safe order; applicant identities remain preserved. ADR 047.

- 2026-08-31: Admission academic selection no longer exposes semester headings. Added category/source-discipline package selection: when all papers under a source are fixed, applicant/admin selects the source (for example History) once and underlying papers are linked automatically; genuine paper choices remain individually selectable. Backend enforces coherent packages. ADR 048.

- 2026-08-31: Applicant/internal application Specialization options now require actual ACTIVE specialization-specific Curriculum Course Mapping usage in the selected Program Offering Curriculum. Empty template-only specializations no longer appear. ADR 049.

- 2026-08-31: Added shared Effective Curriculum Scope authority. Unused Program Template Disciplines/Specializations are excluded from Applicant/Internal Application, Intake/Capacity, Reservation buckets and Selection Rules; Intake activation and Student handoff revalidate current curriculum scope. ADR 050.

## 2026-09-01 — Admission Form dynamic validation, behavior and builder runtime fixes
- Added reusable text/number field constraints without hard-coding domain-specific fields.
- Added compatible cross-field comparisons for NUMBER and DATE fields, enforced on the backend and reflected in form UX.
- Added generic trigger-based copy-from-field behavior with optional target locking; correspondence-to-permanent address copying is supported as configuration rather than special-case code.
- Added reusable DATE age validation with minimum/maximum completed years and current/custom cutoff reference dates.
- Added explicit Panel and Field display-order controls within Admission Form Steps.
- Hardened Add/Edit Field source/panel discovery against nullable hydrated relation arrays.
- Fixed Add Field client white-screen regression caused by missing `AcademicSelect` runtime definition while preserving Academic Applicability and all advanced rules.
- No schema change is introduced by the AcademicSelect runtime fix. See ADR 061–065.

## 2026-09-01 — Admission Form conditional rendering integrity
- Restored ADR 032 any-existing-non-file parent selection across University Base and College Extension templates.
- Added condition editing/removal to Edit Field and circular dependency prevention.
- Fixed runtime dependency parity so academic applicability is evaluated before conditions and dangling conditional children are pruned from effective form payloads.
- No schema change. See ADR 073.

## 2026-09-01 — Admission Form runtime validation parity
- Added ADR 074.
- Added a shared applicant/internal runtime validation helper so Admission Form Setup rules are visibly and consistently enforced wherever the effective form renders.
- Public and college-internal forms now show configured validation hints and live inline violations for text length/input mode, number bounds/precision, date-age rules, and NUMBER/DATE cross-field comparisons.
- Public step-by-step navigation now evaluates the complete configured field-rule set before unlocking the next step.
- College internal rendering now restores native required constraints for visible dynamic controls while backend validation remains authoritative.

## 2026-09-01 — Admission Form builder delete action integrity
- Added ADR 075.
- Replaced passive Admission Form Setup structural DELETE forms with explicit confirmed Inertia delete actions at University and College scope.
- Delete failures now surface dependency validation messages instead of appearing to do nothing.
- Hardened DRAFT Step deletion around RESTRICT condition/comparison/copy-rule source foreign keys: internal dependencies are removed transactionally, while external dependencies and submitted application values continue to block deletion.
- Panel deletion continues to preserve fields by moving them directly under the Step; Field dependency protection remains unchanged.
- No schema change.

## 2026-09-01 — Admission Form copy-rule runtime payload parity
- Fixed effective Admission Form payload to include active copy rules and comparison rules.
- Public Applicant form and College Add Application/Edit Draft can now execute configured field-copy behavior.
- Added applicability-safe handling for dangling advanced-rule dependencies.
- Added ADR 076.

## 2026-09-02 — User visibility and duplicate identity integrity
- Added ADR 077 for hierarchy-driven University/College user visibility.
- University Users now requires College/Institute context and College filtering for College Staff.
- College Users requires same-College `COLLEGE_STAFF` ownership and displays active College-scoped roles, including College Administrator visibility in the list.
- Applicant identities are isolated from internal staff Access Management.
- User email is normalized before validation/persistence and duplicate normalized emails are rejected across University and College creation/update flows.


## 2026-09-02 — Test Cleanup User/Role Access Data
- Added Users and Roles to System Maintenance → Test Data Cleanup.
- Added individual dependency-aware cleanup for internal University/College staff users and custom roles.
- Added `RESET-ACCESS-TEST-DATA` full User & Role test reset.
- Current actor, SUPER_ADMIN identities, applicants, system roles, permissions, audit logs and operationally referenced access records remain protected.
- See ADR 078.

## 2026-09-02 — Role owner institution visibility
- Added ADR 079.
- University Role Management now resolves College-owned custom roles to the owning College name/code.
- Added `Owner / Institution` column and owning-College filter.
- University-owned and Global roles remain clearly labeled; role ownership is kept separate from user-role assignment scope.
- No schema change.

## 2026-09-03 — Access creator-hierarchy isolation
- Corrected University User and Role visibility so College-created access records no longer appear in University administration.
- University-created users/roles remain visible and controllable by University administration when created on behalf of an affiliated College.
- Added immutable creation provenance (`created_by_user_id`, `created_by_scope_type`) to `users` and `roles`, with historical backfill from creation audit actors.
- Enforced the same creator-hierarchy rule on list queries, summaries, role options, edit/status/password-reset and user-role assignment direct actions.
- College Users/Roles remain strictly College-owned views and continue to show both University-created-on-behalf and College-created records for that College.

## 2026-09-03 — Test Data Cleanup access reset service restoration
- Fixed System Maintenance → Test Data Cleanup HTTP 500 caused by the controller calling missing `TestDataCleanupService::accessResetPreview()` and `fullAccessReset()` methods.
- Restored ADR 078 dependency-aware User/Role preview and cleanup behavior, including individual Users/Roles cleanup and the dedicated `RESET-ACCESS-TEST-DATA` action.
- Cleanup continues to preserve the current actor, SUPER_ADMIN identities, applicants, system roles, permissions, audit logs, and users/roles with operational RESTRICT references.
- No database schema change.

## 2026-09-03 — College Roles University UI consistency
- Aligned College Roles with the established University Roles list presentation: summary cards, search/status filters, tabular role details, visible permission/user counts, status badges, labeled actions, pagination, and a header-level Create Role dialog.
- Preserved College ownership, creator provenance, College-scoped authorization, role lifecycle, and permission-management behavior; this is a UI/list-query consistency change, not a scope-policy change.
- College Role creation remains College-owned and continues to preserve whether the creator is University- or College-origin through the existing creator-provenance rule.
- No database schema change.

## 2026-09-03 — Admission Seat Allocation / Consumption
- Added the next Admission Processing milestone after Merit / Roster Generation: generated ranked Application Choices can now consume one exact physical Intake seat.
- Added Open/Unreserved and Vertical reservation capacity enforcement with transaction/row locking and one-active-seat-per-Application protection.
- Added separate Horizontal quota applicability/target-fulfilment persistence so Horizontal quotas never create extra physical capacity.
- Added auditable allocation, cancellation and re-allocation flow; future Admission Confirmation is the downstream consumer.
- Locked Reservation Plan mutation/deactivation after a generated Merit roster depends on that plan.
- Added College-scoped `view`, sensitive `allocate`, and sensitive `cancel` permissions and sidebar/page integration.
- Extended Full Academic Test Reset to remove Horizontal allocation children and Seat Allocation rows before immutable Merit rows.
- Main hierarchy completeness remains tracked: Batches, Sections and College Academic Calendar are still pending College Academic Setup milestones and are not considered completed by this Admission work.

## 2026-09-03 — Admission Document Verification gate + logically linked Seat Allocation
- Main hierarchy review identified the mandatory `Document Verification` stage between generated Merit / Roster and Seat Allocation; implemented it rather than allowing Seat Allocation to bypass the hierarchy.
- Added application-level document verification plus per-uploaded-document review for dynamic Admission Form FILE/IMAGE values, private College-scoped document download, VERIFIED/DEFICIENT finalization, and audit events.
- Seat Allocation now requires a transaction-locked VERIFIED Document Verification row and persists its exact ID as a required RESTRICT FK.
- Seat Allocation/Reservation Consumption remains tied to immutable Merit rank, exact Intake bucket and locked Reservation Plan; vertical physical-seat and horizontal-target rules are unchanged.
- Test Data Cleanup now includes Document Verification and deletes its child review items in correct dependency order during Full Academic Reset.
- College Academic Setup mandatory pending sequence remains tracked: Batches -> Sections -> College Academic Calendar.
- Status: IMPLEMENTED — OWNER QA REQUIRED. Next Admission step after QA: Admission Confirmation / Approval.

## 2026-09-03 — Test Data Cleanup navigation compaction
- Replaced the increasingly tall wrapped Test Data Cleanup module-button strip with one compact grouped section selector.
- Grouped cleanup targets into Access & Security, Admission Processing, Admission Setup, and Academic Setup without changing cleanup routes, permissions, dependency checks, or deletion behavior.
- Preserved all 25 existing cleanup targets and the current default Admission Applications selection.
- No database schema, authorization, or cleanup-service behavior change.

## 2026-09-03 — Test Data Cleanup compact reset summary
- Consolidated the Full User & Role Test Reset and Full Academic Test Reset into one compact Quick Test Resets card.
- Replaced the always-expanded academic table-count grid with summary totals plus an on-demand native details disclosure, so the maintenance page does not keep growing vertically as more cleanup tables are added.
- Kept all existing reset routes, confirmation codes, dependency-aware cleanup behavior, preserved-record rules, and authorization unchanged.
- Retained the grouped Cleanup section selector introduced in the prior UI cleanup, so both the section navigation and reset summaries scale without creating a long page.
- No external CSS, new frontend dependency, backend behavior change, or database migration.

## 2026-09-03 — Applicant Test Data Cleanup by Program Offering
- Added an `Applicants` section to Test Data Cleanup so applicant test accounts can be removed individually rather than being permanently preserved with no direct cleanup path.
- Applicant rows show their linked Program Offering(s), derived through Admission Application -> Admission Cycle -> Program Offering, and can be filtered Program-Offering-wise; unlinked registrations remain discoverable through a dedicated filter option.
- Applicant cleanup is dependency-aware and removes the applicant's Admission test graph child-first before deleting Applicant Profile and login User.
- Applicant cleanup is blocked after Student promotion/linkage or when downstream Admission/Student lifecycle records exist.
- Existing User/Role cleanup continues to exclude applicants; applicant identities are deleted only through the explicit Applicants cleanup section.
- Added audit event `TEST_APPLICANT_CLEANED`.
- No database schema change and no migration required.

## 2026-09-03 — Test Data Cleanup applicant offering query fix
- Fixed Applicant cleanup preview failure caused by selecting a non-existent `college_program_offerings.code` column.
- Program Offering labels now use the linked `program_templates.code` and `program_templates.name`, matching the actual Program Offering schema.
- No database schema or cleanup-policy change.

## 2026-09-03 — Generated Merit / Roster individual test cleanup
- Added a dedicated `Generated Merit / Roster` target to System Maintenance -> Test Data Cleanup.
- Cleanup operates at the generated roster / Selection Rule version level, not per candidate rank, preserving ranking integrity.
- Cleaning removes only the generated `college_admission_merit_entries`; Selection Rule, Applications/Choices and normalized Scores are preserved so the same test scenario can be corrected and regenerated.
- Cleanup is blocked once Seat Allocation consumes any Merit entry; Seat Allocation test records must be cleaned first.
- Added audit event `TEST_COLLEGE_ADMISSION_MERIT_ROSTER_CLEANED`.
- No database schema change and no migration required.

## 2026-09-03 — Prevent duplicate applicant applications for the same offering/cycle
- Enforced one active application per Applicant + College + Admission Cycle. Because each Admission Cycle is bound to one Program Offering, this prevents a second application for the same Program Offering in the same cycle while still allowing the same applicant to apply to a different Program Offering/cycle.
- Public Admission Application now reports `ALREADY_APPLIED` with the existing Application Number instead of presenting the form as submittable.
- Application creation serializes on the applicant user row and re-checks DRAFT/SUBMITTED applications inside the transaction, preventing duplicate creation from multiple tabs/concurrent requests.
- WITHDRAWN applications are not treated as active duplicates; a later re-application remains possible unless a future admission policy changes that rule.
- Test Data Cleanup now labels existing same-applicant/same-cycle duplicates as `DUPLICATE APPLICATION` in Admission Applications so old test duplicates can be identified and cleaned individually after downstream dependencies are removed.
- No database schema change.

## 2026-09-03 — Admission Application cleanup owns its child data
- Corrected Test Data Cleanup so Admission Application-owned records are not treated as blockers after the independently processed downstream chain has been cleared.
- Direct Admission Application cleanup now removes its Program Choices, Dynamic Field Values, Academic Preferences, Curriculum Course Choices, individual Interview/Evaluator rows, normalized Scores, and Document Verification header/items in child-first order.
- Private uploaded files referenced by dynamic application field values are deleted from the Laravel `local` disk before their database references are discarded, preventing orphan files in `storage/app/private`.
- Merit entries, Seat Allocations, Admission Confirmation records and Student references remain safety blockers and must be cleaned first.
- No database schema change.

## 2026-09-04 — Admission Confirmation / Approval
- Added the Admission Confirmation milestone after VERIFIED Document Verification and ACTIVE Seat Allocation.
- Added canonical `admissions` table with exact upstream Admission transaction references, stable Admission Number and CONFIRMED/REVOKED lifecycle.
- Confirmation never recalculates Intake capacity, Reservation consumption, Merit ranking or normalized Score; the existing Seat Allocation remains authoritative.
- Added transactional confirm/re-confirm and reasoned revoke flow with College-scope authorization and audit events.
- Confirmed Admissions now block Seat Allocation cancellation; revoked Admissions leave the seat allocated until an explicit Seat Allocation cancellation is performed.
- Added `college_admission_confirmation.view`, `.confirm` and `.revoke` permissions, College sidebar entry and Inertia page.
- Extended Test Data Cleanup with Admission Confirmation listing/cleanup and Full Academic Reset ordering before Seat Allocation deletion.
- Student master creation and Applicant -> Student identity enablement remain deferred to Student Enrollment / Lifecycle.
- Next mandatory implementation block after Owner QA: Batches -> Sections -> College Academic Calendar, then Student Enrollment / Lifecycle.

## 2026-09-04 — Admission Confirmation QA accepted + Batch Management implemented
- Owner QA accepted the Admission Confirmation / Approval lifecycle, including confirmed-seat cancellation protection, revoke -> release-protection behavior, explicit seat cancellation and capacity restoration.
- Implemented College Academic Setup -> Batch Management as the next frozen milestone.
- Added canonical `batches` table linked only to `college_program_offerings`; Batch does not duplicate Program/Curriculum/Session or seat-capacity authority.
- Added create/update/activate/deactivate service rules, exact College scope, audit events and dependency-safe future Section/Student guards.
- Added `college_batch.view/create/update/enable/disable` RBAC permissions; enable/disable are sensitive and default-granted to protected SUPER_ADMIN/COLLEGE_ADMIN.
- Added Batch Management page and College Academic Setup navigation entry.
- Test Data Cleanup now lists/cleans Batches and Full Academic Reset removes Batches before their Program Offering parents.
- Next frozen milestone after Owner QA: Section Management, then College Academic Calendar, then Student Enrollment / Lifecycle.


## 2026-09-04 — Section Management implemented
- Owner QA accepted for Batch Management, including parent Intake/Program Offering protection UX.
- Added `sections` as the operational child of `batches` with unique Batch-scoped code, INACTIVE-by-default lifecycle, audit fields and RESTRICT parent FK.
- Added College-scoped Section CRUD/lifecycle service, controller, requests and RBAC (`college_section.view/create/update/enable/disable`).
- Added College Academic Setup -> Sections navigation and Section Management page showing inherited Batch/Program/Curriculum/Session/Intake context.
- Section activation requires ACTIVE Batch, Program Offering and Intake and never changes seat capacity/reservation/admission counts.
- ACTIVE Sections now block parent Batch deactivation; backend dependency protection remains authoritative.
- Added Section-aware Test Data Cleanup and dependency-safe full Academic Reset ordering (Sections before Batches).
- Next frozen milestone after Owner QA: College Academic Calendar, then Student Enrollment / Lifecycle.

## 2026-09-04 — Section Management Batch-tree UI refinement
- Replaced the flat College Sections table with a Batch-centric parent/child layout so the academic hierarchy is visually explicit.
- Program, Academic Session, Curriculum and approved Intake are now displayed once at Batch level instead of being repeated on every Section row.
- Renamed the capacity context to `Parent Program Intake` and added explicit copy that the Intake is shared parent context, not per-Section capacity.
- Moved `Add Section` into each eligible ACTIVE Batch card; the selected Batch is fixed during creation to reduce wrong-parent assignment.
- Sections remain child rows with their own code, notes, ACTIVE/INACTIVE lifecycle and edit/status actions.
- Existing backend hierarchy, RBAC, audit, Intake authority, Reservation, Seat Allocation and Admission behavior are unchanged.

## 2026-09-04 — Section Management compact accordion refinement
- Refined the Batch-tree UI into a compact Batch accordion so large numbers of Batches/Sections do not create an excessively long page.
- Batch cards are collapsed by default and show Program, Academic Session, Curriculum, approved parent Intake and Section summary inline.
- Sections render only when the user expands the specific Batch through `Show Sections`; each Batch expands/collapses independently.
- `Add Section` remains available in the Batch header even while the Section list is collapsed.
- Section child rows were tightened vertically while retaining edit and ACTIVE/INACTIVE actions.
- No external CSS/dependency, database, API, RBAC, audit, admission, reservation or Intake-capacity behavior changed.

## 2026-09-04 — Section Management existing-pattern UI alignment
- Replaced the custom Batch accordion interaction with the same compact expandable-row pattern already used by Academic Structure.
- Kept Batch as the visual parent and kept Program/Session/Curriculum/approved Intake context shown once.
- The `Sections` disclosure now uses the existing Chevron + label + right-aligned summary pattern, and expands into a compact bounded section list.
- No database, API, RBAC, admission-capacity or hierarchy rule changed.
- UI consistency rule reinforced: reuse an established ERP interaction pattern before introducing a parallel module-specific pattern.


## 2026-09-04 — Section QA accepted + College Academic Calendar implemented
- Owner QA accepted Section Management: three Sections activated successfully, approved Intake remained 120 and ACTIVE Sections correctly protected parent Batch deactivation.
- Implemented College Academic Calendar as adoption of the existing same-University University Academic Calendar rather than a duplicate calendar authority.
- Added `college_academic_calendars` and `college_calendar_overrides` with RESTRICT source references and non-destructive ACTIVE/INACTIVE lifecycle.
- University events remain authoritative; College override is allowed only when the exact ACTIVE University event has `allow_college_override = true`.
- College override stores a mandatory reason and must remain within the inherited Academic Session date range.
- Added College-scoped RBAC, audit events, existing-pattern expandable event UI, navigation and Test Data Cleanup support.
- No College-only free-standing calendar event is introduced in this milestone; that would require a separate future decision.
- Next frozen milestone after Owner QA: Student Enrollment / Lifecycle.

## 2026-09-04 — College Academic Calendar date-picker consistency fix
- Replaced the native browser date inputs in College Calendar override create/edit with the ERP shared `DatePicker` component already used across Academic Sessions, Admission Cycles, Applications, Audit Logs and other project screens.
- Override Start/End dates now use the established calendar-icon/dialog interaction, validation styling and hidden form-value contract.
- Picker bounds inherit the Academic Session date range; End Date cannot be selected before the chosen Start Date.
- No database, API, RBAC, University-governance or College override business rule changed.



## 2026-09-04 — Curriculum Course countable / non-countable credit treatment
- Added explicit `credit_counting` to Curriculum Course / Paper Mapping with `COUNTABLE` and `NON_COUNTABLE` values.
- Existing mappings default to `COUNTABLE`; numeric credit remains owned by the Curriculum Slot and is not changed or forced to zero.
- Added Credit Treatment to mapping create/edit and mapping list UI, backend validation, audit snapshots and clone/amendment preservation.
- `NON_COUNTABLE` is now the canonical downstream signal for excluding inherited Slot credits from applicable student earned / degree-credit totals.
- Course Category Group remains independent from Credit Treatment. GPA/SGPA/CGPA inclusion is deliberately not conflated with this flag.
- This prerequisite is implemented before Student Enrollment / Lifecycle so downstream student credit calculations have an explicit contract.


## 2026-09-04 — Credit Treatment ownership correction: Mapping -> Curriculum Slot
- Moved `credit_counting` from Curriculum Course / Paper Mapping to Curriculum Slot after QA exposed ambiguity in Term Required Credits.
- Added corrective migration that preserves earlier `NON_COUNTABLE` selections by promoting them to the parent Slot, then removes the mapping-level column.
- Curriculum Slot Add/Edit and Slot list now own/show Credit Treatment; Course / Paper Mapping no longer asks for it.
- Curriculum Credit Summary now excludes `NON_COUNTABLE` Slots from Required Credits and Maximum Credits while still displaying each Slot's numeric academic credit.
- Clone and Curriculum Amendment flows preserve Slot-level Credit Treatment.
- Canonical ADR/page/database/current-state documentation updated before Student Enrollment / Lifecycle.
## 2026-09-04 — Course Mapping Credit Treatment stale-column QA fix
- Removed the leftover `Credit Treatment` table header from Mapped Courses / Papers after Credit Treatment ownership moved to Curriculum Slot.
- This restores correct column alignment: Course Code -> Status -> Actions. No backend, database, or credit-calculation behavior changed.


## 2026-09-04 — Fee Foundation / Enrollment gate
- Inserted Fee Clearance dependency before Student Enrollment (ADR 095).
- Added University + College Fee Head and Fee Structure foundation with item-level amount, mandatory, installment and enrollment-clearance flags.
- Added exact Program Offering scope for College Fee Structures and Academic Session + optional Program Template scope for University Fee Structures.
- Added guarded INACTIVE/ACTIVE lifecycle, RBAC, audit events, routes/sidebar and Inertia management UI.
- Preserved future reusable online/offline payment adapter contract; payment execution is deferred to the Payment milestone.

## 2026-09-04 — Fee Foundation Academic Session column QA fix
- Fixed Fee Management Academic Session ordering to use canonical `academic_sessions.starts_on` instead of nonexistent `start_date`.
- Resolves the University Fee Management 500 error without changing fee business rules.

## 2026-09-04 — Fee Category Master correction
- Replaced the hard-coded Fee Head category dropdown with configurable `fee_categories` master data.
- Added University Fee Categories and College-local Fee Categories; College Fee Management also inherits University categories read-only.
- Seeded the original common categories as ACTIVE University defaults so existing setup remains immediately usable.
- Migrated legacy `fee_heads.category` values to `fee_heads.fee_category_id`; Fee Category is now a real FK-backed master.
- Fee Category remains degree/program independent. Degree/Program-specific applicability and amounts continue to belong to Fee Structures.
- Added category lifecycle guards, audit events and independent University/College RBAC.
- Governing decision: ADR 096.


## 2026-09-04 — Fee Management compact tabbed UI QA refinement
- Replaced the vertically stacked Fee Categories, Fee Heads and Fee Structures sections with three compact in-page tabs and live record counts.
- Added client-side search to each Fee Management workspace and 10-row pagination for Categories and Heads; Fee Structures are also paged in groups of 10.
- Preserved the existing Fee Structure -> Fee Items expandable interaction and all RBAC/business rules.
- Applied the same interaction model to University and College Fee Management because both reuse the shared Fee Manager component.
- No database, route, permission, or fee calculation behavior changed.

## 2026-09-04 — Fee Management tab visual refinement
- Refined the Fee Categories / Fee Heads / Fee Structures workspace selector into three responsive icon-backed navigation cards.
- Added clear active-state emphasis and compact contextual counts while preserving the existing tab behavior, search, pagination, RBAC and fee business rules.
- No database, route, permission, or fee calculation behavior changed.


## 2026-09-04 — University / College Fee Structure applicability correction
- Added explicit `MANDATORY` / `OPTIONAL` College Applicability to University Fee Structures; existing University structures are safely backfilled as OPTIONAL.
- Added explicit College adoption records for OPTIONAL University structures; adoption remains a reference to the authoritative University structure and never copies ownership.
- Matching MANDATORY University structures now surface at College Fee Management as automatically effective/read-only; OPTIONAL structures expose Adopt / Stop Using actions.
- Preserved College independence: no University structure is required before a College can create its own, and a mandatory University structure still allows separate College-local additional charges.
- Added `college_fee_structure.adopt` RBAC and documented the future Fee Demand contract in ADR 097.

## 2026-09-04 — Fee Management Test Data Cleanup coverage
- Added Fee Management entities to System Maintenance → Test Data Cleanup:
  - Fee Structures
  - Fee Heads
  - Fee Categories
- Fee Structure cleanup deletes its test-only Fee Items and University→College adoption rows in the same transaction before removing the structure.
- Future Fee Demand references are treated as operational blockers; setup cleanup must not remove fee setup already consumed by transactional fee data.
- Fee Head cleanup is blocked while referenced by Fee Structure Items or future Fee Demand Items.
- Fee Category cleanup is blocked while referenced by Fee Heads.
- Seeded University baseline Fee Categories (Admission, Tuition, Registration, Examination, Library, Lab, Hostel, Transport, Development, Certificate, Other) are protected from Test Data Cleanup; custom University/College categories remain cleanable when unused.
- Added a dedicated Fee Management section in the cleanup UI.

## 2026-09-04 — University Fee Head inheritance correction
- College Fee Management now includes University-owned Fee Heads alongside College-local Fee Heads.
- University Fee Heads are marked as University-owned and remain read-only on the College side; College edit/status actions are not exposed for inherited heads.
- College Fee Structure Items can now reference an ACTIVE University Fee Head or an ACTIVE Fee Head owned by the current College.
- Backend validation still rejects Fee Heads from another University or another College.
- Fee Item selectors label inherited University Fee Heads so ownership is clear and duplicate College masters are discouraged.
- Governing decision: ADR 098.

## 2026-09-04 — Fee Collection Basis / Academic Period foundation
- Added ADR 099.
- Fee Structures now distinguish financial collection basis from the Program Template/Curriculum academic term structure.
- Added ONE_TIME, PER_TERM, PER_ACADEMIC_YEAR, SPECIFIC_TERM and SPECIFIC_ACADEMIC_YEAR modes.
- Specific College terms are validated against ACTIVE Curriculum Terms of the exact Program Offering.
- Existing structures default safely to ONE_TIME for owner review.
- No Fee Head, Curriculum or Program Template duplication/change is introduced.

## 2026-09-04 — Recurring Fee period amounts + Curriculum-source correction
- Added period-specific Fee Item amount overrides so `PER_TERM` / `PER_ACADEMIC_YEAR` no longer implies the same amount in every period.
- Default Fee Item amount remains the fallback; only periods with different rates need overrides.
- College period choices now come from ACTIVE Curriculum Terms of the exact Program Offering instead of inventing terms from Program Template duration.
- Academic-year billing groups are derived from those actual Curriculum terms and become eligible only when the required terms for the year are present and ACTIVE.
- Added activation guard for recurring College structures with no eligible Curriculum periods.
- Changing collection basis or Program/Offering scope clears stale period overrides.
- Added ADR 100 and `fee_structure_item_period_amounts`.
- Fee Structure Test Data Cleanup now removes period-amount override rows before Fee Items, preserving FK-safe cleanup.
- College `SPECIFIC_ACADEMIC_YEAR` selection now also validates that all terms required for that year exist and are ACTIVE in the attached Curriculum; Program Template duration alone is not enough.

## 2026-09-04 — Fee Item period applicability correction
- Added explicit per-period applicability for recurring Fee Items so a Fee Head may legitimately not exist in one semester/year without fake ₹0/₹0.01 amounts.
- Added `fee_structure_item_period_exclusions`; no exclusion means applicable by default, while an exclusion means future Fee Demand must create no line for that Fee Head in that period.
- Preserved period-specific amount overrides independently: applicable + blank override uses the default Fee Item amount; applicable + override uses the override amount.
- College period options remain sourced from ACTIVE Curriculum Terms of the exact Program Offering.
- Academic-year availability is derived from Curriculum term sequence groups and appears only when all required terms in the group are ACTIVE.
- Changing Fee Structure collection basis/scope now clears both stale amount overrides and stale period exclusions.
- Fee Structure Test Data Cleanup now deletes period exclusions before Fee Items.
- Governing decision: ADR 101.

## 2026-09-04 — Fee Structure period-first configuration UI
- Reworked both University and College Fee Structure configuration to `Fee Structure -> Billing Periods -> Fee Items`.
- Replaced the flat Fee Item list / visible default+override workflow with period cards containing period-scoped `Add Fee Item` actions.
- College Semester/Term cards come only from ACTIVE Curriculum Terms on the exact Program Offering; College Academic Year cards appear only for complete Curriculum-derived term groups and show which terms they cover.
- University recurring period cards are derived from the selected Program Template because University Fee Structures are not attached to one exact College Curriculum.
- The same Fee Head can now be conveniently reused across periods with different effective amounts, or omitted from a period entirely, without creating duplicate Fee Head/Item rows.
- Per-period ACTIVE totals are displayed in the structure UI.
- Existing period amount/exclusion tables remain the storage model; no migration or new RBAC is required.
- Governing decision: ADR 102.

## 2026-09-04 — Fee Structure configured total + one-time Admission Fee semantics
- Added `Configured Total` to University and College Fee Structure headers.
- Total sums only ACTIVE/applicable effective Fee Item amounts across derived Billing Periods; period exclusions are omitted and period overrides are respected.
- Clarified in UI/docs that Configured Total is setup information, not a student's outstanding/demand/payment balance.
- `ADMISSION + ONE_TIME` now labels its single Billing Period as `One-Time Admission Charge`.
- Reconfirmed that Admission Fee is optional, Application/Form Fee is separate, and enrollment clearance remains item-driven.
- Governing decision: ADR 103.

## 2026-09-04 — Period-first applicability UX simplification
- Removed the redundant `Applicable in this billing period` checkbox from University and College period-first Fee Item dialogs.
- A Fee Item configured inside a Billing Period is now treated as applicable by presence in the user-facing workflow.
- Period-specific amount is entered directly in that period; charges that do not apply to another period are simply not added there.
- Existing period amount/exclusion persistence remains an internal compatibility detail; no migration or RBAC change is required.
- Added an explicit end-of-day Resume Checkpoint to NEXT_WORKFLOW so Fee Foundation QA can continue from the same point in the next session.
- Governing decision: ADR 104.

## 2026-09-05 — Fee University Curriculum Period Consistency Fix
- Fixed University Fee recurring periods being synthesized from Program Template duration (for example showing Semester 1–6 even when the actual Curriculum only defines Semester 1–2).
- Added `fee_structures.curriculum_id` with short MySQL-safe FK/index names.
- University recurring/specific period structures now require an exact ACTIVE + APPROVED Curriculum matching Program + Academic Session.
- University Billing Period UI now derives from that Curriculum's ACTIVE terms.
- University Fee applicability to Colleges now respects exact Curriculum when configured.
- Added ADR 105 and QA resume checkpoint.


## 2026-09-05 — University Fee Curriculum selector current-version-only fix
- Filtered University Fee Curriculum selector through canonical `Curriculum::currentApproved()` scope.
- Added backend protection rejecting previous/superseded Curriculum IDs for University Fee Structure create/update.
- Preserved existing historical Fee Structure references; no silent rebinding on Curriculum amendment.
- Added ADR 106 and synchronized Current State, Next Workflow, and Relationship Map.

## 2026-09-05 — Period-specific Fee Item policy
- Added `fee_structure_item_period_settings`.
- Recurring Semester/Academic-Year charges can now have different Mandatory, Enrollment Clearance Required, Installment Allowed, display order and status values for the same Fee Head.
- Updated University and College period-first Fee Item dialogs to expose these controls for every billing period.
- Configured totals and Fee Structure activation now respect effective period-level status.
- Existing explicit recurring period amounts inherit the old shared Fee Item policy during migration.
- Test Data Cleanup now removes period settings.
- Added ADR 107.

## 2026-09-05 — University Fee read-only view + College duplicate-charge protection
- Added College-side `View Structure` for applicable University Fee Structures before/after OPTIONAL adoption and for MANDATORY policies.
- View is read-only and shows configured total, Billing Periods, Fee Heads, period amounts and period-specific charge policy.
- College local Fee Structures remain allowed as supplements.
- Added backend guard blocking the same Fee Head on overlapping billing coverage when a matching University structure is effective (MANDATORY or adopted OPTIONAL).
- Non-adopted OPTIONAL University structures do not block College-local charges.
- Added ADR 109 and QA resume checkpoint.

## 2026-09-05 — Fee adoption/activation overlap integrity fix
- Owner QA found a lifecycle bypass: after stopping an OPTIONAL University structure, a conflicting College Fee Head could be configured; the University structure could then be adopted again and the College structure could also be activated.
- Moved the no-double-charge rule to the effectiveness boundaries in addition to item-save validation.
- College Fee Structure activation now blocks conflicts with effective University structures.
- OPTIONAL University adoption now blocks conflicts with already ACTIVE College structures.
- University structure activation/re-activation now blocks conflicts that would become effective for matching Colleges (MANDATORY, or OPTIONAL with existing adoption).
- Overlap comparison uses ACTIVE positive-value charges and underlying academic term coverage, including specific-period bases.
- Added ADR 110. No migration or RBAC change.

## 2026-09-05 — Fee Item delete/remove action
- Added a trash/remove action beside Fee Items while a Fee Structure is INACTIVE.
- Recurring structures remove the Fee Head only from the selected Billing Period; other configured periods remain unchanged.
- If the removed recurring period was the last applicable period, the shared Fee Item is deleted completely.
- ONE_TIME and specific-period structures delete the Fee Item directly.
- ACTIVE structures still require deactivation before Fee Items can be removed.
- Added University + College DELETE endpoints using existing Fee Structure update permissions and audit logging.
- Added ADR 111. No migration or RBAC change.

## 2026-09-05 — Applicable Fee Demand implemented
- Added `fee_demands` and `fee_demand_items` immutable admission-level snapshots.
- Added exact effective-structure resolution for University MANDATORY, University OPTIONAL+ADOPTED and College local structures.
- Added exact billing-period amount/policy snapshot including Mandatory, Enrollment Clearance Required and Installment Allowed.
- Added College Fee Demands UI, RBAC, duplicate-demand/mixed-currency/duplicate-head guards and reasoned unpaid cancellation.
- Added ADR 112 and table specification. Payment/Adjustment/Fee Clearance remains intentionally downstream.

## 2026-09-05 — Applicable Fee Demand implemented
- Added `fee_demands` and `fee_demand_items` immutable admission-level snapshots.
- Added exact effective-structure resolution for University MANDATORY, University OPTIONAL+ADOPTED and College local structures.
- Added exact billing-period amount/policy snapshot including Mandatory, Enrollment Clearance Required and Installment Allowed.
- Added College Fee Demands UI, RBAC, duplicate-demand/mixed-currency/duplicate-head guards and reasoned unpaid cancellation.
- Added ADR 112 and table specification. Payment/Adjustment/Fee Clearance remains intentionally downstream.

## 2026-09-05 — Fee Demand workflow correction (ADR 115)
- Admission Confirmation now triggers initial applicable Billing Period 1 demand automatically when fee items apply.
- Manual candidate demand generation is retained only as Period 1 recovery for confirmed admissions without an active initial demand.
- Later-period one-by-one demand generation is intentionally removed from the primary workflow.
- Added Program Offering → Academic Policy automatic resolver with Curriculum > Program > Degree Level > University precedence and ambiguity protection.
- Later-period bulk demand is explicitly gated on authoritative Student Enrollment + Academic Progression results; Fee Management does not recalculate promotion eligibility.
- Admission revocation cancels unpaid/unadjusted fee demands and blocks revocation when payment/adjustment activity exists.
- Added fee demand `generation_mode` provenance (`ADMISSION_AUTO`, `MANUAL_RECOVERY`, `BULK_PERIOD`).

## 2026-09-05 — Fee Demand Test Data Cleanup
- Added Fee Demands to Test Data Cleanup → Fee Management.
- Fee Demand cleanup deletes child demand items before the demand and audits the operation.
- Demands with payment/adjustment or downstream financial activity are protected from test cleanup.
- Admission cleanup now recognizes Fee Demand as downstream operational data.
- Full Academic Test Reset now counts and removes Fee Demand Items / Fee Demands before Admissions.
- Added ADR 116.

## 2026-09-05 — Fee Structure billing-context reinterpretation fix
- Added ADR 117.
- Prevented collection-basis / billing-scope changes while Fee Items are configured.
- Removed the old behavior that cleared only some period child rows and could cause a parent item amount to fall back across newly derived periods.
- Fee Collection Basis is visibly locked in the edit UI until Fee Items are removed.

## 2026-09-05 — Fee Setup-driven Program Offering bulk demand (ADR 118)
- Replaced the Fee Demand bulk placeholder with an executable Fee Setup-driven bulk workflow.
- Bulk contexts now come from effective University MANDATORY / adopted OPTIONAL + College local ACTIVE Fee Structures.
- Demand Purpose and Collection Basis are respected: term-wise, academic-year-wise, specific period and one-time contexts are not redefined inside Fee Demand.
- Admission-stage generation now includes ADMISSION-purpose charges plus only first-period ACADEMIC items explicitly marked Enrollment Clearance Required; EXAMINATION/OTHER are excluded from Admission Confirmation.
- First Academic period can bulk-generate for the confirmed admission cohort; later periods remain blocked until authoritative Student Enrollment + Academic Progression results exist.
- University Academic Policy continues to auto-resolve from Program Offering and is displayed as the later-period eligibility authority.
- Added source-item/billing-period duplicate protection so a clearance item already demanded at Admission stage cannot be charged again by Academic bulk generation.
- EXAMINATION and OTHER bulk contexts are visible only when Fee Setup exists but remain blocked until their authoritative target-eligibility workflows exist.
- Added demand context/billing label/bulk-run provenance columns for auditable demand history.

## 2026-09-05 — Fee Demand refundable snapshot (ADR 119)
- Added `fee_demand_items.is_refundable` as an immutable demand-time snapshot of `fee_heads.is_refundable`.
- New demand generation now carries Fee Head refundability together with Mandatory, Enrollment Clearance and Installment policy.
- Demand detail now shows Refundable / Non-refundable status.
- Existing pre-column demand items are one-time backfilled from their referenced Fee Head at migration time.

## 2026-09-05 — Fee Demand Individual generation (ADR 120)
- Added `Bulk Cohort | Individual` generation modes to the existing Fee Setup-driven demand workflow.
- Individual mode selects one CONFIRMED admission from the selected Program Offering for the currently executable first Academic period.
- Individual generation uses the same Fee Setup, eligibility, duplicate protection and demand-item snapshot logic as Bulk generation; it cannot bypass a blocked academic period.
- Added `INDIVIDUAL_PERIOD` demand provenance. No migration or new RBAC permission.

## 2026-09-05 — Compact grouped Fee Demand register (ADR 121)
- Removed Manual Recovery from the normal College Fee Demand page; backend recovery capability is retained for exceptional maintenance only.
- Grouped Fee Demands by Admission/Application so one candidate/student occupies one top-level register row regardless of how many Semester/Year/Admission demands exist.
- Added collapsed-by-default `Demands (n)` groups and separately collapsed demand-item details.
- Added search across Application Number, Admission Number, candidate/student name, Demand Number and billing-period/context text.
- Added compact client-side pagination at 10 admissions per page and aggregate Total/Mandatory/Enrollment-Clearance/Outstanding summaries per candidate.
- No migration, RBAC, fee calculation or financial-history mutation.

## 2026-09-07 — Fee Demand Individual searchable student selector
- Added ADR 122.
- Replaced full preloaded Individual candidate select with debounced server-side search.
- Search supports candidate/student name, Application No. and Admission No., scoped to the selected ACTIVE Program Offering and CONFIRMED cohort.
- Result set capped at 30; Fee Demand page no longer preloads all confirmed admissions per offering.
- No migration, RBAC change, or internal/page-specific CSS.

## 2026-09-07 — Project-wide mutation feedback + Fee Demand duplicate messages (ADR 123)
- Shared server-side `toast` flash data through Inertia globally and wired the existing project Sonner hook to page flash props.
- Individual Fee Demand duplicate/no-new-charge protection now returns a visible informational outcome instead of failing silently.
- Bulk Fee Demand reruns now distinguish `0 generated / skipped existing` as an informational result and report clear created/skipped/error counts.
- Established the project-wide rule that every user-triggered mutation, including protected no-ops, must return visible outcome feedback.
- No migration, RBAC change, page-specific CSS, or fee-calculation rule change.


## 2026-09-07 — Global toast Inertia-context crash fix
- Fixed blank-page regression caused by calling `usePage()` from the global Sonner Toaster hook.
- Restored the project-safe `router.on('flash', ...)` bridge for session toast feedback.
- Added ADR 124 to protect provider/context boundaries for future global feedback changes.

## 2026-09-07 — Mutation feedback delivery fixed in Inertia AppLayout
- Fixed server success/info/warning/error flash messages not appearing even though Fee Demand mutation endpoints returned them.
- Moved the flash listener out of the global Sonner Toaster and into `AppLayout`, where Inertia `usePage()` context is valid.
- Global Toaster is now presentation-only, preventing the previous blank-page regression.
- Added fallback error toast for validation failures when no explicit mutation toast is supplied.
- Added ADR 125 to make visible mutation feedback a reusable ERP-wide rule.

## 2026-09-07 — Scholarship / Concession / Waiver Foundation
- Added University and College Fee Benefit Scheme setup.
- Added Fee Head targeting, Fixed/Percentage calculation, optional cap, Reservation Category eligibility, Automatic/Manual approval mode and ACTIVE/INACTIVE lifecycle.
- College sees ACTIVE University schemes read-only and may configure College-local schemes.
- Added RBAC permissions and sidebar navigation under Fee Management.
- Added ADR 126. QA pending.

## 2026-09-07 — Scholarship / Benefits Test Data Cleanup (ADR 127)
- Added `Scholarship / Benefits` to Test Data Cleanup → Fee Management.
- Added University + College scholarship scheme listing and per-scheme cleanup.
- Cleanup deletes Fee Head/Reservation Category mappings before the scheme and preserves the referenced masters.
- Added future operational-reference blocking for scholarship application/allocation/sanction/financial usage.
- Added Full Academic Test Reset counts/deletion for scholarship schemes and mappings.
- Added audit event `TEST_FEE_SCHOLARSHIP_SCHEME_CLEANED`.

## 2026-09-07 — Scholarship Session Context Eligibility (ADR 128)
- Filtered University Scholarship/Benefits Academic Sessions to sessions with current ACTIVE + APPROVED Curricula.
- Filtered College sessions to those represented by ACTIVE Program Offerings backed by current ACTIVE + APPROVED Curricula.
- Current eligible Academic Session now sorts first and is selected by default.
- Added matching backend validation for session/offering integrity.

## 2026-09-07 — Scholarship Action Icon Consistency (ADR 129)
- Updated Scholarship / Benefits Edit, Activate and Deactivate controls to reuse the existing project Lucide icon + Button pattern.
- Edit now uses Pencil, Activate uses Power, and Deactivate uses PowerOff while retaining text labels.
- No RBAC, route, lifecycle, database or business-rule change.


### 2026-09-07 — ADR 130 Student Benefit Assignment / Sanction / Demand Adjustment
- Added `fee_student_benefits` and item-level `fee_student_benefit_items`.
- Added College Student Benefits UI with server-side Demand search and applicable ACTIVE University/College scheme resolution.
- Added Admission reservation-category snapshot eligibility, manual approval/rejection and automatic sanction.
- Approved benefits post auditable Fee Demand adjustments while preserving original demand amounts.
- Added separate operational RBAC and Test Data Cleanup support.

## 2026-09-07 — Candidate Reservation Category source corrected
- Added optional Admission Form Mapping → Candidate Reservation Category field.
- Seat Allocation auto-fills mapped category or requires manual fallback confirmation.
- Candidate category and consumed physical seat category are stored separately.
- Student Benefits category eligibility now uses candidate category snapshot only.
- Added ADR 132.

## 2026-09-07 — Seat Allocation confirmed-admission guard + toast consistency (ADR 133)
- Restored the previously QA-passed linked Admission status projection removed during the Candidate Reservation Category change.
- `Cancel Allocation` is disabled for `CONFIRMED` Admission and backend cancellation again explicitly guards `CONFIRMED` only, preserving the valid REVOKED → explicit cancellation flow.
- Removed page-specific failure-alert behavior from the restored guard path; success and validation failure now use the existing ADR 125 Sonner toast architecture.
- Candidate Reservation Category mapping/manual fallback from ADR 132 remains unchanged.

## 2026-09-07 — ADR 135 Seat Allocation category recommendation + merit priority
- Restricted candidate reservation selector to ACTIVE VERTICAL categories.
- Added own-category reserved-seat recommendation with OPEN capacity fallback.
- Disabled non-applicable reserved buckets in the allocation UI.
- Added backend cross-category reserved-seat guard.
- Added OPEN higher-merit pending-candidate guard.
- Added same-category reserved merit-priority guard for mapped categories.

## 2026-09-07 — ADR 136 Application category auto-resolution
- Fixed Seat Allocation failing to auto-resolve a category already selected in the submitted Admission Form.
- Seat Allocation now reads the application value of the `CANDIDATE_RESERVATION_CATEGORY` system field first, with legacy form mapping as fallback.
- Added synthetic `General / Unreserved` candidate classification so Intake does not need a reserved `GEN` bucket.
- Candidate category options now contain only General + ACTIVE VERTICAL Reservation Categories; Horizontal categories are excluded.
- Preserved ADR 135 physical-seat recommendation, merit-priority and cross-category guards.
## 2026-09-08 — ADR 137 Bulk Student Benefit academic tree + controlled selection
- Added Individual / Bulk Assignment mode switch to College Student Benefits.
- Added ACTIVE scheme-first bulk workflow with candidate resolution against existing Fee Demands.
- Added compact collapsed hierarchy: Degree Level → Degree → Discipline → Student.
- Added group-level and student-level checkbox selection while preserving manual final beneficiary choice.
- Added search across academic/student/category context and summary counts/totals.
- Added duplicate exclusion and final server-side per-demand eligibility re-check; stale/ineligible selections are skipped safely.
- Bulk assignment reuses ADR 130 calculation/sanction logic; no gross Fee Demand mutation.


### 2026-09-08 — ADR 137 QA hotfix: Bulk Student Benefit initial render and scheme loading
- Fixed stray `000` text in Bulk Assignment. Cause: numeric `bulkSchemeId` (`0`) was used directly in React `&&` rendering conditions, so React rendered the zero values. Conditions now explicitly use `bulkSchemeId > 0`.
- Bulk Benefit schemes are now supplied with the initial Student Benefits Inertia page payload instead of depending on a second client-side request just to populate the scheme dropdown.
- This makes existing ACTIVE University/College schemes immediately available when Bulk Assignment is opened and avoids an empty dropdown when the extra fetch is unavailable/stale-cached.
- Added an explicit empty-state message when the College genuinely has no ACTIVE applicable benefit schemes.
- Bulk candidate loading remains scheme-driven and is still revalidated server-side before assignment.

### 2026-09-08 — ADR 138 Student Benefit bulk candidate fix + auditable removal
- Fixed Bulk Student Benefit candidate loading after Program Template disciplines were moved to many-to-many. Bulk candidate resolution now reads the student's saved Admission Academic Preference discipline instead of the removed `program_templates.discipline_id` column.
- Bulk candidate fetch errors are now surfaced in the UI instead of silently appearing as an empty eligible-student list.
- Added auditable `Remove Benefit` action for mistaken assignments.
- PENDING benefits can be removed before sanction with no Fee Demand financial change.
- APPROVED benefits can be removed only through controlled reversal: the sanctioned adjustment is subtracted from Fee Demand `adjusted_amount`, outstanding/status are recalculated, and the benefit record is retained as `CANCELLED` with reason/user/time for audit.
- Original Fee Demand gross amount remains immutable.

### 2026-09-08 — ADR 139 Student Benefit selection clearing + eligibility diagnostics
- Added explicit `Clear` / `Clear selection` control for the staged Individual Fee Demand selection. Selecting a demand is only temporary UI context until a benefit is actually assigned; clearing it does not mutate Student/Admission/Fee Demand data.
- Editing the Individual search text after a demand was selected now automatically clears the stale selection/scheme state so a different student/demand can be searched immediately.
- Individual scheme options now expose the concrete ineligibility reason instead of showing only `Not eligible`; when every scoped scheme is ineligible, the page shows a compact reason summary.
- Bulk candidate resolution now aggregates server-side ineligibility reasons while scanning scoped Fee Demands. The Bulk empty state reports scanned/ineligible/already-assigned counts and the actual exclusion reasons rather than presenting an unexplained zero-student result.
- No scholarship eligibility rule was loosened: candidate-category checks, actual Fee Head coverage, remaining eligible amount, scheme scope, duplicate protection and final submit-time revalidation remain authoritative.

## 2026-09-08 — Student Benefit individual duplicate-state visibility (ADR 140)
- Individual Student Benefit scheme resolution now exposes an existing active assignment for the exact Fee Demand + Scheme combination.
- PENDING / APPROVED combinations remain visible in the scheme dropdown as disabled `Already assigned — <STATUS>` options instead of appearing assignable again.
- The Fee Demand itself remains searchable because another eligible scheme may still be applied to the same demand.
- REJECTED / CANCELLED historical benefit records do not block a fresh assignment.
- Backend duplicate protection remains authoritative at final submission; this change aligns Individual UX with Bulk `Already assigned` handling.

## 2026-09-08 — Fee Demand approved-benefit audit visibility (ADR 141)
- Fee Demand details now explicitly separate Gross Demand, Paid, Scholarship / Concession / Waiver adjustments, and Outstanding.
- Approved Student Benefits are listed by scheme snapshot/code and sanctioned amount so the reason for a reduced outstanding balance is visible directly from the Demand Register.
- Demand fee-item rows show their approved benefit adjustment amount where applicable.
- Gross Fee Demand remains immutable; this is audit visibility over the existing approved adjustment ledger, not a recalculation or mutation of the original charge.
- Only APPROVED benefits contribute to the displayed financial adjustment list; PENDING/REJECTED/CANCELLED records do not appear as active reductions.

## 2026-09-08 — Fee Installment execution foundation (ADR 142)
- Student Benefits QA completed, including CANCELLED benefit re-assignment eligibility.
- Started the next Fee Phase implementation: Installment scheduling/execution.
- Added `fee_installment_schedules` with demand/item ownership, ordered installment amount/due date, ACTIVE/CANCELLED history and actor fields.
- Added college-scoped installment view/manage permissions.
- Installment schedules are available only when the Fee Demand Item snapshot says `Installment Allowed = Yes`.
- Schedule total must equal current net item payable after APPROVED Student Benefit adjustment; gross demand remains immutable.
- Added auditable pre-collection schedule replacement and explicit Test Data Cleanup coverage for installment schedules.
- Late Fine remains a separate next policy implementation after Installment QA.

## 2026-09-08 — ADR 142 bulk/common installment scope correction
- Extended Installment Scheduling from student-only execution to ERP-level common/bulk scheduling.
- Added hierarchy/context-scoped bulk preview for installment-enabled Fee Heads and student demand items.
- Added discipline-group + individual controlled selection; all eligible students in the selected Fee Head are selected by default.
- Added percentage-based common schedule so the same policy can apply safely when Student Benefits make student net payable amounts differ.
- Added server-side bulk revalidation, collection-start protection, auditable replacement, and `FEE_INSTALLMENT_BULK_SCHEDULE_SET` audit events.
- Individual installment schedule remains available for authorized exceptions.

### 2026-09-08 — ADR 142 bulk installment QA UI refinement
- Moved Common Schedule above discipline/student groups on Bulk Installment Schedule.
- Discipline/student groups now default collapsed and expand only on explicit operator action, preventing large cohorts from creating an excessively long page.
- Discipline-level select/deselect remains usable while the group is collapsed.

## 2026-09-08 — ADR 143 Student Benefit → Installment adjustment integration
- Integrated approved Student Benefits with existing ACTIVE installment schedules instead of leaving pre-benefit installment totals stale.
- Manual benefit approval now offers `PROPORTIONAL` (recommended), `NEXT_UNPAID_FIRST`, and `CUSTOM` installment adjustment modes when the affected student already has an ACTIVE installment schedule.
- Adjustment is student-specific and Fee-Demand-Item-specific; other students using the same common/bulk installment plan remain unchanged.
- AUTOMATIC benefit schemes use PROPORTIONAL adjustment by default.
- Added installment `paid_amount`, stored allocation percentage/source metadata, Benefit adjustment mode/snapshot, and audit events for approval/reversal integration.
- Approved-benefit removal restores the exact pre-benefit schedule when safe; if the schedule changed later, it recalculates proportionally instead of overwriting newer history.
- Paid installment history is explicitly immutable for future Payment Collection + Allocation integration.
- Added Test Data Cleanup coverage note for test-only benefit/installment adjustment history.

### 2026-09-08 — Student Benefits bulk route regression hotfix
- Restored missing Student Benefits bulk routes in `routes/web.php`: bulk schemes, bulk candidates, and bulk assignment.
- Controller methods already existed; the regression was route registration only.
- Preserved ADR 137 controlled bulk assignment behavior and ADR 143 installment-adjustment integration without changing business rules.



## 2026-09-09 — ADR 144 Student Benefit Register scalability / consistency
- Replaced the Student Benefit Register's fully expanded record list with a compact table + on-demand View/detail area.
- Added server-side Session/Programme Offering/Billing Period/Scheme/Status/Search filters and 25/50/100 pagination.
- Current Academic Session is selected automatically; operators can switch to historical Sessions.
- Kept Batch out of this pre-enrollment-capable workflow in accordance with the documented Enrollment → Programme/Batch/Semester assignment lifecycle.
- Renamed installment adjustment presentation `Reduce next unpaid first` → `Apply to Next Installment First` without changing the persisted `NEXT_UNPAID_FIRST` mode.
- Added Custom Distribution live summary: New Payable, Allocated and Remaining.
- Added ADR 144 as the project-wide consistency rule for future student operational registers and selectors.

## 2026-09-09 — ADR 145 Fee Demand compact scalable register
- Replaced Fee Demand admission-level giant cards with a compact table and on-demand View/Details expansion.
- Added server-side admission-group pagination (25/50/100); only the visible page's demand details are loaded.
- Added Current Session default using canonical `academic_sessions.is_current`, optional historical Session, Programme Offering, Status and search filters.
- Programme Offering filter is constrained to the selected Session; Batch is intentionally not forced before Enrollment.
- Preserved Fee Demand financial calculations, Student Benefit audit, installment actions and cancellation behavior.
- Extended ADR 144 high-volume UI consistency rule to Fee Demand and future large transactional/student registers.

## 2026-09-09 — ADR 146 Student Benefit grouped register + Discipline/Age
- Changed Student Benefit register pagination/grouping from one row per benefit to one parent row per Fee Demand/admission context.
- Multiple schemes for the same demand now appear under `Benefits (N)` child rows, preventing duplicate-looking student/application rows.
- Added Discipline and Age to parent identification context and an optional Discipline filter.
- Preserved exact scheme-level approval, removal, audit and installment-adjustment actions.
- No database migration or financial rule change.

## 2026-09-09 — ADR 147 Fee Demand Discipline/Age context
- Added Discipline, Age and Programme to Fee Demand admission-group rows.
- Added Session/Programme-aware Discipline filter and discipline search support.
- Preserved server-side 25/50/100 admission-group pagination and all existing demand financial/action behavior.
- No database migration.

## 2026-09-09 — ADR 148 Test Data Cleanup bulk selection / module cleanup
- Added a checkbox in front of cleanable Test Data Cleanup records across standard modules, Curriculum and Academic Policies.
- Added header Select All for all cleanable records in the current view.
- Added `Clean Selected (N)` so multiple QA records can be removed with one confirmation/action.
- Added `Clean All Cleanable (N)` to clean the complete selected module without clicking every record individually.
- Module-wide cleanup ignores optional UI narrowing filters and operates on the full module, while dependency-blocked/protected records are preserved.
- Added one explicit module confirmation code (`CLEAN-<MODULE>-TEST-DATA`) for bulk actions; existing individual confirmation behavior remains unchanged.
- Academic Policy bulk cleanup remains version-chain-aware and safely de-duplicates versions belonging to an already-cleaned chain.
- Existing cleanup service logic, audit events, environment guard and `test_data_cleanup.manage` permission remain authoritative.
- No database migration.

## 2026-09-09 — ADR 149 Reservation Category form-mapping simplification
- Removed manual `Candidate Reservation Category Field` selection from the College Application Entry mapping dialog.
- New mappings automatically bind the ACTIVE `CANDIDATE_RESERVATION_CATEGORY` system-purpose field when present.
- Prevented arbitrary SELECT/RADIO fields from becoming the new authoritative candidate reservation source through this mapping screen.
- Seat Allocation now identifies automatic category source as `SYSTEM_FORM_FIELD`; pre-existing mapping behavior is retained only as `LEGACY_FORM_MAPPING` fallback.
- Updated Seat Allocation helper text to distinguish system-captured, legacy-mapped and manually confirmed candidate category sources.
- No database migration.

## 2026-09-09 — ADR 150 Direct Admission Document Verification / Seat Allocation completion
- Fixed Direct Admission candidates being absent from Document Verification and Seat Allocation because both screens previously depended exclusively on Merit / Roster rows.
- Added a Direct Admission Document Verification context for SUBMITTED Direct applications; existing document review/finalize rules remain mandatory.
- Direct application submission now locks an Intake + seat-bucket processing choice from the saved academic preference without a Selection Rule; pre-existing submitted Direct applications are repaired on VERIFIED document finalization.
- Added Direct Seat Allocation endpoint/service path that enforces VERIFIED documents, Intake/bucket ownership, ADR 149 candidate Reservation Category, Vertical/Horizontal reservation rules and physical capacity while intentionally bypassing Merit priority.
- Added migration making Merit Entry, Score, Selection Rule, Merit Rank and Final Weighted Score nullable on seat allocations for Direct rows. Regular allocations still populate them.
- Admission Confirmation now renders Direct allocations without fake rank/score and resolves programme/session from the allocation Intake when no Selection Rule exists.
- Added ADR 150 and QA/test-cleanup coverage notes.

### 2026-09-09 — ADR 151 Admission revoke finance cascade + Admission Initial bulk installment eligibility
- Fixed Admission Confirmation revoke integration so linked active installment schedules and PENDING benefit assignments are cancelled together with the linked unposted Fee Demands; finance history is preserved, not deleted.
- Fee Demand operational register now defaults to non-cancelled records, with Cancelled available as an explicit history filter.
- Decoupled Bulk Installment scope from Fee Demand generation scope.
- Added existing-demand-derived `installment_contexts`, including automatically generated `ADMISSION_INITIAL / MIXED` demands when a Fee Demand Item snapshot allows installments.
- Bulk Installment preview/store now accepts `ADMISSION_INITIAL` and `MIXED` scopes and continues to exclude cancelled demands and collection-started replacements.

## 2026-09-09 — ADR 152 Bulk Installment Scope Normalization
- Fixed Bulk Installment candidate regression after ADR 151.
- Normalized legacy/null Fee Demand context values consistently in both scope discovery and candidate loading.
- Added safe fallback from Fee Demand Item purpose/charge basis/source period.
- Improved Bulk Installment preview error text when a selected scope contains no active installment-enabled items.
- No migration required.

## 2026-09-09 — ADR 154 Bulk Installment route regression fix
- Restored missing `fee-installments/bulk-preview` GET route.
- Restored missing `fee-installments/bulk` POST route.
- Preserved individual installment route and ADR 152/153 scope normalization.

## 2026-09-09 — ADR 155 Student Benefit bulk route regression hotfix
- Restored missing Student Benefit bulk scheme/candidate/store routes.
- Kept Bulk Installment bulk-preview/bulk-store routes intact.
- Added route-preservation guardrail to project documentation.
- No migration.

## 2026-09-09 — ADR 156 Student Benefit register admission-level grouping
- Fixed duplicate-looking Student Benefit parent rows when the same Application/Admission had benefits on different Fee Demands or billing periods.
- Parent grouping now uses Admission/Application identity instead of `fee_demand_id`.
- Individual and Bulk-assigned benefits for the same admission are merged into one compact parent row.
- Expanded child rows now show the exact Fee Demand/Billing Period and assignment Source so transaction-level traceability is preserved.
- Server pagination now counts Admission/Application groups; existing filters continue to constrain visible child benefits and aggregates.
- No schema migration and no Student Benefit financial/eligibility rule change.

## 2026-09-09 — ADR 157 — Student Benefit parent row compacted
- Renamed Student Benefit Register column `Programme / Periods` to `Programme`.
- Removed parent-row billing-period and demand-count text such as `Admission Initial · 1 demand`.
- Kept Demand / Period and source details inside expanded Benefits rows.
- No migration required.


## 2026-09-09 — ADR 159 Late Fine UI consistency hotfix
- Removed nested `AppLayout` from Late Fine / Penalty page.
- Late Fine now follows the existing Fee Management page shell and compact spacing.
- No external CSS added; existing project components and project DatePicker remain in use.
- No business logic, RBAC, migration, cleanup, or calculation behavior changed.

## 2026-09-09 — ADR 160 Fee Billing Period Standard Due Date + Demand snapshot
- Added Standard Due Date to Fee Setup at Fee Head/Billing Period level.
- Added per-period due date storage for recurring fee structures and item-level due date storage for one-time/specific-period structures.
- Added activation guard so ACTIVE effective charges cannot be activated without a Standard Due Date.
- Added immutable-by-default due-date snapshot to generated Fee Demand Items.
- Fee Demand expanded rows now show the snapshotted Standard Due Date.
- Clarified installment interaction: installment due dates control scheduled installment timing; the original Fee Demand Item due date remains the policy snapshot.
- No legacy dates are guessed/backfilled; new Fee Demand generation blocks if an effective charge still has no Standard Due Date.
- ADR 158 Late Fine QA is deferred until it is aligned with the new due-date source hierarchy.

## 2026-09-09 — ADR 161 Curriculum Term Academic Periods + Fee Due Date boundary
- Fixed Academic Calendar/Fee Due Date architecture without creating a duplicate Semester master.
- Existing Curriculum Terms are authoritative for Semester/Year identity; Academic Calendar now assigns session-specific Start/End dates to those terms.
- Calendar Events may be mapped to a Curriculum Academic Period and are backend-validated inside that period; general University events remain supported.
- Different curricula (for example UG/PG programmes) can have different Semester 1 dates in the same Academic Session.
- ADR 160 Standard Due Dates are now backend-validated against the applicable Curriculum Term/year boundary and revalidated on Fee Structure activation.
- Existing calendar events are not guessed/backfilled into terms.
- Test Data Cleanup now treats calendar-term mappings as Curriculum dependencies and removes them safely during full Academic Reset.
- ADR 158 Late Fine remains QA-deferred until updated to consume the finalized due-date hierarchy.


## ADR 162 — 2026-09-09
- Fixed ADR 161 migration recovery for MySQL partial DDL application.
- Existing `academic_calendar_term_periods` is preserved on retry; missing Academic Calendar Event linkage is added safely.
- No business/UI behaviour changed.

## 2026-09-09 — ADR 163 Academic Period selector UX
- Replaced the single long Curriculum-Term selector with hierarchical Curriculum -> Academic Period selection.
- Added Curriculum search/filtering by Curriculum/Programme name or code.
- Removed internal revision-chain codes from user-facing option labels.
- Already configured Terms remain unavailable; exact `curriculum_term_id` persistence is unchanged.
- No schema or business-rule change.

## 2026-09-09 — ADR 164 Curriculum Effective Window → Academic Period → Fee Due Date linkage
- Hardened the Academic Period chain so Curriculum Term Start/End dates must fall inside the intersection of Academic Session and Curriculum Effective From/To.
- Only ACTIVE Terms from the current APPROVED Curriculum version for the same University/Session can be assigned Academic Period dates.
- Academic Period modal now shows and enforces the effective date window using the project DatePicker; backend validation remains authoritative.
- Fee Setup now defensively verifies Academic Calendar periods are still inside Curriculum Effective From/To before accepting Standard Due Dates.
- Fee Setup UI receives the same effective Academic Period bounds and labels Standard Due Date as linked to that Curriculum Academic Period.
- No duplicate Semester/Year master and no new migration.
- ONE_TIME / Admission Initial remains intentionally outside Semester/Year forcing.
- Academic Calendar + Fee Setup integration QA is required before Late Fine QA resumes.

## ADR 165 — Test Data Cleanup Bulk Route Regression Fix
- Restored `POST /admin/system-maintenance/test-data-cleanup/bulk-clean` → `TestDataCleanupController@bulkCleanup`.
- Fixes HTTP 404 from Test Data Cleanup `Clean Selected` / `Clean All Cleanable` actions after later shared route-file replacements.
- Preserves Student Benefit bulk, Installment bulk, Late Fine, Academic Calendar period, and other existing route contracts in the patched route file.
- No cleanup business-rule or data-model change.

## 2026-09-09 — ADR 166 Admission Form Academic Applicability + Profile Preview
- Reworked Admission Form Academic Applicability to searchable multi-select dimensions.
- Multiple values within a dimension are OR; configured dimensions combine as AND.
- Reused existing field-scope rows; no migration/new master introduced.
- Added backend hierarchy/current-curriculum validation for multi-value scope selections.
- Preserved existing scopes during unrelated field edits unless applicability is explicitly submitted.
- Candidate Profile Photo system field now renders as an image in public Final Preview and no longer appears only as a filename in Application Details.
- Runtime remains authoritative from Admission Cycle → Program Offering → academic hierarchy + linked Curriculum.


## ADR 167 — DatePicker Dynamic Year Range Synchronisation
- Fixed blank Year dropdown when DatePicker `min`/`max` changes dynamically.
- Picker now moves to the nearest valid month/year after Curriculum/date-boundary changes.
- No migration.

- ADR 168: Fixed shared DatePicker Year trigger rendering so a valid bounded year can no longer appear blank; the trigger now renders the authoritative calendar view year directly.

## ADR 170 — Fee Due Grouping / Mandatory / Installment Contract
- Added canonical server-side due-date grouping projection for future Payment Collection.
- Same-date Fee Heads can form one collection-facing Due Group without merging accounting liabilities.
- Mandatory and non-mandatory subtotals remain distinct.
- ACTIVE installment schedules replace the parent item due date as the payable-date source for scheduled principal.
- Approved benefits and paid installment amounts are respected in the projection.
- Payment Collection must allocate one transaction back to exact Fee Demand Item / Installment Schedule rows.

## 2026-09-10 — ADR 171 Payment Collection + Deterministic Allocation
- Added `fee_payments` and `fee_payment_allocations`.
- Added College Payment Collection page/controller/service/models.
- Added `college_fee_payment.view` and `college_fee_payment.collect` permissions.
- Added Fee Management sidebar entry `Payment Collection`.
- Reused ADR 170 Due Group projection and made non-installment open amount payment-aware.
- Implemented partial payments and overpayment guard.
- Implemented mandatory/optional opt-in contract.
- Implemented exact installment and late-fine allocation.
- Updated Late Fine outstanding projection to subtract POSTED fine allocations and protect paid fine revisions from silent recalc mutation.
- Added Test Data Cleanup for QA payments and dependency guards for paid Installment/Late Fine rows.
- Preserved route contracts, including existing bulk Fee Benefit, Installment, Late Fine, Direct Admission, Academic Calendar term-period and Test Data Cleanup routes.

## ADR 172 — Payment Collection layout consistency fix
- Removed nested `AppLayout` from Payment Collection page.
- Restored canonical single application shell/header.
- No business logic, route, RBAC or schema change.

## 2026-09-10 — ADR 173 Payment Due-Now + Student Grouping
- Payment Collection now defaults to dues payable on/before Payment Date; future dues require explicit `Include future dues` opt-in.
- Partial payment remains allowed; original Fee Demand/Installment liability is never editable from collection.
- Backend allocation enforces the future-due boundary and preserves deterministic allocation/overpayment protection.
- Open Payables now paginates by Student/Admission and groups all session Fee Demands under one expandable student row for high-volume scalability.
- No migration required. Owner QA required before Payment Collection sign-off.

## ADR 174 — Payment Collection Upcoming Due Visibility
- Added server-derived Next Due date/amount to each open Fee Demand.
- Added student-level Next Due aggregation across grouped demands.
- Added compact Next Due visibility to the grouped student register and expanded demand register.
- Future dues remain informational/default-excluded until due or explicitly included for advance collection.
- No migration.

## 2026-09-10 — ADR 175 Payment Allocation Priority + Dialog Reset
- Fixed mixed mandatory/optional allocation so category priority is enforced across the entire selected payable set: Mandatory Principal → Mandatory Late Fine → Optional Principal → Optional Late Fine.
- Oldest due date is now the secondary ordering rule inside each category, not ahead of mandatory/optional priority.
- Fixed Collect Payment dialog stale amount after successful posting/reopen; each open now resets from the latest server-derived payable state.
- No migration; no route/RBAC/schema change.

## 2026-09-10 — ADR 176 Late Fine Rule Lifecycle + Unused Rule Cleanup
- Converted Late Fine rule row actions to compact Edit / Activate-Deactivate / Delete icons.
- Added safe deletion for unused INACTIVE Late Fine Rules.
- Any calculation history blocks rule deletion; QA Late Fine Charges must be cleaned first.
- Added audit event `FEE_LATE_FINE_RULE_DELETED`.
- Fixed Late Fine Register Due Date / As Of display to human-readable dates instead of raw ISO values.
- No migration and no new permission.
- Payment Collection functional + RBAC QA status recorded: view-only, collector and no-permission runtime checks passed; Rahul optional balance was fully cleared to zero.

## 2026-09-10 — ADR 177 Payment Gateway Configuration + Fee Head Mapping
- Added College gateway configuration for Razorpay, Cashfree Payments and PayU.
- Added encrypted credential storage with TEST/LIVE and ACTIVE/INACTIVE lifecycle.
- Added Fee Head → Product/Item Code + optional Settlement Code mapping per gateway.
- Added College RBAC: `college_payment_gateway.view` and `college_payment_gateway.manage`.
- Added Fee Management sidebar entry `Payment Gateways`.
- No provider checkout/webhook is activated yet; existing Fee Payment + Allocation remains accounting authority.

## 2026-09-10 — ADR 178 Payment Gateway Save All + Global Toast Event Reliability
- Added `Save All` for Fee Head → gateway Product/Settlement Code mappings while retaining per-row Save.
- Added bulk mapping endpoint with the existing College scope/RBAC contract.
- Fixed repeated identical server toast messages globally by introducing unique shared Inertia feedback event IDs.
- Updated the shared `useFlashToast` hook so consecutive identical success/error feedback is rendered as separate events across the ERP.
- No migration; no payment-accounting or provider-checkout behavior changed.

## ADR 179 — Payment Gateway Validation Errors Stay In-App
- Replaced expected Payment Gateway activation/edit business-rule `422 abort` responses with normal redirect + error-toast responses.
- Missing Key ID/Secret now blocks activation while preserving the Payment Gateway page and INACTIVE status.
- ACTIVE gateway credential-edit guard now also stays inside the ERP UI.
- Reinforced project-wide UX rule: expected user-correctable business validation must not expose Laravel/Symfony exception pages.
- No migration or route change.

## 2026-09-10 — ADR 180 Multiple Gateway Credential Profiles + Fee Head Routing
- Corrected Payment Gateway configuration to support multiple credential profiles for the same provider under one College.
- Removed the old database uniqueness that allowed only one provider row per College.
- A Razorpay/Cashfree/PayU provider may now have multiple independent Key ID/Secret profiles for different institutional collection purposes.
- Relabelled gateway setup around Credential Profile / Profile Name; Merchant/Account Reference is optional.
- Fee Head mapping remains profile-specific and Product/Settlement Codes are preserved.
- Enforced one ACTIVE credential-profile mapping per College + Provider + Environment + Fee Head by automatically deactivating competing profile mappings.
- Existing ADR 178 Save All/repeated-toast and ADR 179 in-app validation behavior are preserved.

## 2026-09-10 — ADR 181
- Fixed ADR 180 migration for MySQL error 1553 when dropping `cpg_college_provider_uq`.
- Added a dedicated `college_id` FK-support index before removing the provider uniqueness constraint.
- Made index changes safe to retry after a failed/partial migration attempt.

### 2026-09-10 — ADR 182: Fee Head Independent Gateway Credential Routing
- Corrected Payment Gateway UX so Fee Heads are routed independently instead of presenting every Fee Head inside every credential-profile card.
- Added centralized TEST/LIVE Fee Head Payment Routing register with credential profile, Product Code and Settlement Code per Fee Head.
- Added `Not assigned` route and bulk Save All Routing action.
- Added backend environment-scoped routing bulk endpoint with safe in-app validation errors.
- TEST and LIVE routes remain independent; no finance accounting tables changed.

## ADR 183 — Provider-aware Payment Gateway configuration
- Removed global Product Code requirement from Fee Head routing.
- Added provider capability registry for Razorpay, Cashfree, PayU and NTT DATA/Atom.
- Added encrypted provider-specific configuration storage.
- Added provider-specific credential labels and validation.
- NTT DATA/Atom Product ID resolves from profile default or Fee Head override.
- Payment transaction adapters remain the next implementation; configuration alone is not treated as a live gateway integration.
