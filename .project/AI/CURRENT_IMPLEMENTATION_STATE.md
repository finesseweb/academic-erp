
## ADR 169 — Academic Period date-range integrity (QA pending)
Academic Period DatePicker boundaries are now normalized from ISO datetime values and expose only years inside `Academic Session ∩ Curriculum Effective From/To`. ACTIVE Semester/Year ranges already assigned to another Term of the same Curriculum are unavailable in the picker and are protected by server-side overlap validation. Different Curricula may still run in parallel. Fee Setup continues to consume these Academic Period boundaries under ADR 164.

## Late Fine / Penalty Rules — ADR 158 — 2026-09-09
- **IMPLEMENTED / QA PENDING.** College Late Fine Rules now calculate separate auditable penalties against ACTIVE installment due dates.
- Rule scope: Program Offering + Fee Head; FIXED/PERCENTAGE; ONE_TIME/PER_DAY/PER_WEEK; Grace Days; optional cap.
- Gross Fee Demand and Fee Demand Item remain immutable. Active fine is shown separately; `Payable incl. Fine = Outstanding + Active Late Fine`.
- Recalculation creates revision history (`ACTIVE -> SUPERSEDED`) instead of overwriting prior fine records; disappearing liability reverses active fine.
- Fee Demand cancellation, Admission Confirmation revocation and pre-collection installment replacement reverse linked ACTIVE late fine charges.
- Late Fine Register uses Current Session by default, optional Programme Offering/search filters and server-side pagination.
- Manual Calculate/Recalculate and daily `fees:recalculate-late-fines` scheduler entry are implemented.
- Test Data Cleanup includes Late Fine Charge revision lineage; master Late Fine Rules are preserved by default.
- Next after QA PASS: Payment Collection + Allocation.


### ADR 153 — Bulk Installment Scope SQL compatibility hotfix (2026-09-09)
- Bulk Installment scope discovery is now compatible with MySQL `ONLY_FULL_GROUP_BY`.
- Normalization occurs per Fee Demand Item in a subquery; aggregation occurs on normalized aliases only.
- This fixes the 1055 `demand_context isn't in GROUP BY` runtime failure introduced during ADR 152 QA.
- No schema/business-rule change.

# Current Implementation State

## Common Academic Approval integration

**QA COMPLETE — 2026-08-25:** Curriculum and Academic Policy common approval workflow has been tested successfully, including role-based Inbox routing, multi-stage Approve, Return, Reject, correction/resubmission and final activation. Backend workflow-type matching is enforced.

### Frozen future rule
Academic Approval is a reusable engine. Future approval-enabled modules must integrate with the same workflow/stage/inbox mechanics while keeping subject-specific validation and final lifecycle behavior in their own handler/service.

## Academic Policy scope enhancement

**IMPLEMENTED / QA PASSED:** Academic Policy supports `UNIVERSITY -> DEGREE_LEVEL -> PROGRAM_TEMPLATE -> CURRICULUM`. Degree Level scope uses the existing active same-University Degree Level Master and `academic_policies.degree_level_id`.

## Academic Calendar

**QA COMPLETE / OWNER ACCEPTED — 2026-08-25:** University Academic Calendar foundation is implemented and tested. It introduces one official calendar per Academic Session plus normalized University calendar events, event-level College override governance, granular permissions, audit logging, Laravel/Inertia UI and date-bound validation.

## College Program Offerings

**IMPLEMENTED_IN_REPLACEMENT_PACKAGE — PENDING_REVIEW:** First College Academic Setup milestone. College selects same-University Program Template, approved active Curriculum and Academic Session; new offering starts INACTIVE and is explicitly activated. College permissions and audit are scope-aware.

### Current gate
Run College Program Offering QA defined in `NEXT_WORKFLOW.md`. Do not begin Intake / Seat Capacity until owner review passes.

## Test Data Cleanup maintenance update — 2026-08-25
- Cleanup Center expanded to cover Academic Calendar, College Program Offerings, Approval Workflows, Degrees and Degree Levels.
- Existing parent cleanup checks now understand Calendar / Program Offering dependencies.
- Full Academic Test Reset available only through the guarded Test Data Cleanup tool.
- Full reset preserves system/access core and uses explicit dependency order; foreign keys remain enabled.


## Program Offering Current-Selection correction — 2026-08-25
- New College Program Offering preselects the University's `ACTIVE + is_current` Academic Session.
- Other eligible PLANNED/ACTIVE sessions remain manually selectable.
- Curriculum selector now exposes only the derived Current `ACTIVE + APPROVED` Curriculum for the selected Program + Session.
- Backend enforces the same Current Curriculum rule; posting a Previous approved Curriculum is rejected.
- Curriculum currentness remains derived from ADR 007 amendment chains; no duplicate `is_current_version` column/flag is introduced.
- Existing Program Offerings are historical references and are never auto-relinked when Session Current status or Curriculum Current version changes.


## College Intake / Seat Capacity — 2026-08-25
- Next College Academic Setup milestone implemented in replacement package.
- Hierarchy: Program Offering -> Intake Header -> optional Discipline/Specialization allocations.
- Supports PROGRAM_ONLY and STRUCTURED capacity modes.
- Structured activation requires exact allocation total.
- University Program Template mappings remain authoritative.
- Status: OWNER_QA_REQUIRED before Reservation / Quota starts.


## Intake admission-specialization correction — 2026-08-25
- Intake allocation levels are now PROGRAM / DISCIPLINE / ADMISSION_SPECIALIZATION.
- Optional academic specializations are excluded from seat capacity by default.
- Program Template specialization mapping gains `is_admission_seat_bearing` (default false).
- Admission Specialization seat rows require that explicit flag.
- Student Lifecycle contract now separates admission seat identity from later academic specialization/elective choice.


## Intake hierarchical capacity correction — 2026-08-25
- Final Intake model: PROGRAM or DISCIPLINE allocation mode.
- Specialization capacity is optional child capacity under a Discipline, not a separate mutually-exclusive Intake mode.
- Discipline totals equal Program capacity on activation.
- Specialization child totals may be less than or equal to Discipline capacity.
- Remaining General Discipline seats are derived automatically.
- Future Student Lifecycle must store Discipline seat allocation plus nullable Specialization seat allocation.
- ADR 015 supersedes the earlier admission-specialization-only interpretation.


## Reservation / Quota / Seat Distribution — 2026-08-25
- Implemented after Intake / Seat Capacity.
- University-owned configurable Reservation Categories support VERTICAL and HORIZONTAL nature.
- College Reservation Plans attach only to effective admission seat buckets: PROGRAM, DISCIPLINE_GENERAL, or SPECIALIZATION.
- Open/Unreserved remaining is derived from bucket capacity minus Vertical reserved seats.
- Horizontal quota overlays the same physical seats and does not create extra capacity.
- Reservation protects dependent Intake capacity/allocation from unsafe mutation.
- Admission/Student lifecycle must preserve physical seat bucket + reservation context.
- Plan-level lifecycle UI correction: every plan now shows its status plus explicit Edit and Activate/Deactivate actions; plan metadata is editable only while INACTIVE and seat-bucket identity remains immutable.
- Reservation lifecycle permissions are synchronized to College roles that already have Reservation update access, so quota editing and required lifecycle completion stay consistent.
- Status: OWNER_QA_REQUIRED before Admission implementation.

## Merit / Roster / Selection Rules — 2026-08-26
- Implemented immediately after Reservation / Seat Distribution according to `HIERARCHY_PATCH_STUDENT_ADMISSION_RESERVATION.md`.
- A Selection Rule belongs to one exact effective Intake admission seat bucket. Reservation is optional per bucket; when defined it must be ACTIVE and its plan id is preserved for traceability.
- Rules are versioned: new versions start INACTIVE; only INACTIVE versions are editable; activating a version retires the previous active version for that seat bucket.
- Selection modes support MERIT, ENTRANCE, INTERVIEW and COMBINED without hard-coding University/Government policy. Merit, Entrance and Interview are first-class normalized scoring components; Combined may use any two or all three with positive weights totaling exactly 100%.
- Rule preserves component-aware normalized qualifying thresholds (Merit / Entrance / Interview / Final Weighted) plus ordered machine-readable tie-breakers, including Interview Score, for later candidate ranking. Free-text tie-break wording is policy notes only.
- Interview execution is intentionally not part of Student Lifecycle: future Admission Processing will schedule/evaluate interviews and persist candidate interview scores; Merit/Roster generation consumes those scores through the ACTIVE Selection Rule; Student Lifecycle starts after admission confirmation.
- Activation requires Program Offering + Intake to remain ACTIVE. Reservation is required to be ACTIVE only when a Reservation Plan is defined for that exact bucket.
- Status: OWNER_QA_ACCEPTED — 2026-08-26. Owner confirmed Selection Rule behavior/UI before starting Student Admission Processing.
- Reservation / Seat Distribution lifecycle UI corrected: plan managers now receive visible Activate/Deactivate controls beside Edit, with update-permission fallback and dependency-safe confirmation.


## Applications / Candidate Eligibility — 2026-08-26
- Implemented as the first transactional Student Admission Processing milestone after Selection Rules.
- Restored/connected the existing Admission Cycle route/sidebar as the Application parent and corrected its activation gate to require an eligible ACTIVE Selection Rule rather than globally requiring Reservation.
- Application header supports DRAFT -> SUBMITTED -> WITHDRAWN.
- Candidate may have ordered Program Choices; each choice consumes the exact ACTIVE Offering + Intake seat bucket, optional ACTIVE Reservation Plan, and ACTIVE Selection Rule.
- Submission revalidates upstream context, records `submitted_at`, and locks the exact Selection Rule version for later Score / Interview / Merit processing.
- Preliminary ELIGIBLE / INELIGIBLE / PENDING is stored per Program Choice and does not duplicate Selection Rule score thresholds.
- Test Data Cleanup / Full Academic Reset dependency graph extended for Applications, Choices, Selection Rules and Admission Cycles.
- Status: OWNER_QA_REQUIRED before Score Capture / Normalization implementation.

### Admission prerequisite repair — 2026-08-26
The Admission Cycle foundation is now explicitly part of the delivered implementation state (table/model/controller/requests/permissions). Applications must reference a real `college_admission_cycles` row. A repair migration also guarantees the structured Selection Rule tie-breaker table exists before Admission processing depends on it.

## Admission Cycle Program Offering Anchor Patch — 2026-08-26
- Admission Cycle is now directly anchored to `college_program_offerings` instead of being configured from Academic Session alone.
- Academic Session remains a derived compatibility snapshot; Program Offering is authoritative for College/Session/Program/Curriculum context.
- Admission Cycle create/edit uses a searchable ACTIVE Program Offering selector.
- Activation validates the exact offering's ACTIVE Intake and at least one ACTIVE Selection Rule; Reservation stays optional per bucket.
- Applications inherit the cycle's Program Offering and can select only seat buckets/specializations from that exact offering.
- Status: IMPLEMENTED — OWNER QA REQUIRED before continuing Score Capture / Normalization.

## Interview Scheduling / Evaluation — 2026-08-27
- Implemented after Score Capture for locked Selection Rules with Interview weight > 0.
- Status: OWNER_QA_REQUIRED before Merit / Roster Generation.
- See `CURRENT_IMPLEMENTATION_STATE_PATCH_INTERVIEW.md`.

## Admission Form Configuration & Internal Application Entry — Stage 1 — 2026-08-27
- Owner approved a prerequisite branch before Merit / Roster so the Admission transaction chain has a proper configurable entry foundation.
- Existing `college_admission_applications` / `college_admission_application_choices` remain authoritative; this branch does not replace or fork the completed Admission work.
- Added University/College-owned Form Templates with assigned manager, governance mode, REGULAR/DIRECT/BOTH applicability, dynamic steps, fields/options, file/image inputs and scoped mappings.
- Added most-specific-wins Application Fee Rules across University/College -> Degree Level -> Degree -> Program -> Offering -> Admission Cycle, with fee/template snapshots on each application.
- Internal Application Entry now resolves the configured template and renders extra fields in the existing ERP theme.
- REGULAR retains Selection Rule lock and downstream Eligibility/Score/Interview/Merit behavior.
- DIRECT shares the Application/Application Choice foundation but may be created/submitted without a Selection Rule so it can later route directly toward seat/admission processing.
- Status: IMPLEMENTED_IN_PACKAGE / OWNER_QA_REQUIRED.
- Frozen hierarchy resume point after acceptance: Interview Scheduling / Evaluation QA -> Merit / Roster Generation.
- See `CURRENT_IMPLEMENTATION_STATE_PATCH_ADMISSION_FORM_STAGE1.md`, ADR 024 and the Stage 1 Page Spec.


### Stage 1 governance correction — University controlled College access (2026-08-27)
- University-side `Admission Form Setup` entry point added.
- University can create/activate locked base templates and dynamic base steps/fields.
- University explicitly enables/disables Admission Form Setup per affiliated College.
- Per-College governance modes supported: University Controlled, University Base + College Extension, College Controlled.
- College application-fee override is independently allowed/denied by University.
- College menu requires University feature enablement + College-scoped RBAC permission.
- Backend College setup routes enforce the same feature gate; direct URL/API access cannot bypass it.
- University sidebar permissions are now evaluated from University scope separately from aggregated College permissions.
- Full Academic Test Reset removes Stage 1 University→College access-control test records as well.

## Patch — Admission Form stale access-control runtime removed (2026-08-31)

- Fixed a Stage 1 regression where College Admission Form Setup queried the removed `college_admission_form_access_controls` table and returned SQLSTATE 42S02 / 1146.
- Admission Form runtime authorization is now exclusively `User -> Role -> Permission -> Scope`.
- Removed active runtime dependency from College controller, University controller, Inertia shared authorization data, and the obsolete University College-access route/UI.
- `allow_college_override` on the University Base template remains the only structural extension governance flag; it does not replace RBAC and does not block College mapping/use of a locked base form.
- No database migration is required for this correction.
- See ADR 035.

## Runtime correction — 2026-08-31 — College Admission Form Setup SSR relation normalization
Status: IMPLEMENTED / QA REQUIRED

College Admission Form Setup now recursively normalizes optional nested Eloquent relation arrays before SSR/render. This addresses the observed `undefined.map` white-screen regression and does not alter Admission Form business relationships or RBAC governance. See ADR 037.

- Applicant verification runtime fix: User uses Laravel `Illuminate\Auth\MustVerifyEmail`. Public form mapping now has `seat_selection_required` default false; applicants are not forced to choose a seat bucket unless College enables it for that mapping.

## Patch — Public Application Academic Preference + Premium Preview (2026-08-31)
- Public application now has a dedicated Logout action and premium theme-aware journey UI.
- Template `SAME_WINDOW`/`NEW_WINDOW` behavior remains respected; `NEW_WINDOW` renders step-by-step with premium progress/navigation.
- The application starts with Program Offering academic selection: Discipline, optional Specialization, mandatory curriculum papers, and curriculum-defined choice papers.
- Public submission is intentionally decoupled from seat capacity/buckets. Seats are processed later in the frozen Admission workflow.
- Final Review & Submit preview added.
- New tables persist applicant academic preference and course selections independently of seat allocation.
- Governing decision: ADR 043.

## Admission Form advanced dynamic field rules + builder runtime stabilization — 2026-09-01
- Admission Form fields now support generic intrinsic validation: text minimum/maximum/exact length and number minimum/maximum/whole-number/decimal-place constraints.
- Generic cross-field comparisons support compatible NUMBER-to-NUMBER and DATE-to-DATE fields with `<`, `<=`, `>`, `>=`, `=` and `!=`; backend validation remains authoritative. Example use case: Obtained Marks <= Total Marks.
- Generic copy behavior supports copying a compatible source field into a target when a configured trigger matches, including optional target locking. Address copying is a configuration use case, not a hard-coded address feature.
- Any DATE field may use dynamic minimum/maximum age rules with current-date or custom-cutoff reference; rules are not tied to DOB or a named field.
- Panel and Field display order are configurable within a Step; lower values render first and existing automatic ordering remains the fallback when order is omitted.
- Add/Edit Field collection discovery is defensive against nullable/malformed hydrated `steps`, `fields` and `panels` arrays.
- Restored the missing `AcademicSelect` runtime helper used by Academic Applicability after browser QA identified `ReferenceError: AcademicSelect is not defined` when opening Add Field. SSR was healthy; this was a client-dialog render regression.
- ACTIVE/RETIRED templates remain structurally frozen; these rules are configured only while the owning template is DRAFT.
- Status: IMPLEMENTED / OWNER_QA_REQUIRED.
- Governing decisions: ADR 061, 062, 063, 064 and 065.

## Test Data Cleanup — Admission Form Template testing deactivation — 2026-09-01
- Test Data Cleanup -> Admission Form Templates now provides `Deactivate for Testing` for ACTIVE templates.
- The guarded maintenance transition is `ACTIVE -> DRAFT`; normal Admission Form Setup remains frozen for ACTIVE templates.
- Exact template Code confirmation, `test_data_cleanup.manage`, and the existing environment guard are required.
- No Admission Form structure or submitted Application is deleted by this action.
- Enabled public applicant mappings for the selected template are disabled automatically before deactivation.
- Audit event `TEST_ADMISSION_FORM_TEMPLATE_DEACTIVATED` records before/after template state.
- No database migration is required.
- Governing decision: ADR 072.

## Admission Form conditional rendering integrity — 2026-09-01
- **IMPLEMENTED:** condition parents may be any existing eligible non-file field per ADR 032; Edit Field can change/remove the condition; cycle detection protects edited dependencies.
- Runtime applicability is resolved before conditions, and conditional children with unavailable sources are removed from the effective Applicant/Internal form payload.
- Backend and React condition semantics remain aligned. See ADR 073.

### 2026-09-01 — Admission Form runtime validation parity
Admission Form field validation is now carried through to both runtime renderers. The public applicant form and College Admission Applications internal entry use the shared `resources/js/lib/admission-field-validation.ts` helper for validation hints and live rule evaluation. This covers allowed text input, min/max/exact text length, numeric min/max/integer/decimal precision, date age rules, and NUMBER/DATE cross-field comparisons. Public NEW_WINDOW step navigation blocks progression on any visible configured-rule violation. Backend `CollegeAdmissionDynamicFieldService` remains authoritative. See ADR 074.

## 2026-09-01 — Admission Form Setup structural delete reliability
University and College Admission Form Setup now use confirmed Inertia router DELETE actions for Draft Template/Step/Panel/Field cleanup (and College mapping removal). Backend Step deletion is dependency-aware: submitted values and external field-rule references block deletion, while condition/comparison/copy rules wholly internal to the deleted Step are removed transactionally before the Step cascade. Structural deletion remains DRAFT-only per ADR 053. See ADR 075.

### Admission Form advanced-rule runtime parity — 2026-09-01
- Copy rules configured in Admission Form Setup are now serialized by `CollegeAdmissionFormResolver::templatePayload()`.
- Public Applicant and College Add Application/Edit Draft receive source field, trigger field, trigger values and read-only state.
- Active copy rules are also reapplied server-side by `CollegeAdmissionDynamicFieldService` before validation/persistence.
- Comparison rule metadata is included in the same effective runtime payload.


### Access-management test cleanup (2026-09-02)
System Maintenance → Test Data Cleanup now includes internal Users and Roles. Internal University/College staff users and custom roles can be cleaned individually when dependency-safe, or together with the dedicated Full User & Role Test Reset. Protected/system identities and roles, applicants, permissions, audit logs and operationally referenced records are preserved. See ADR 078.

### Role ownership visibility (2026-09-02)
University Access & Role Management now exposes the owning institution for College-owned custom roles. `owner_scope_reference=college:<id>` is resolved to College name/code in the University Roles list, with a College filter. University/Global role templates retain their actual owner classification; assignment scope is not confused with role ownership. No migration required. See ADR 079.

## 2026-09-03 — Applicant registration/help correction
- Public Applicant Registration Number is finalized on the applicant's first successful application submission, not at account creation. The final number is rendered from the College's currently saved registration-number format and transactionally reserved sequence.
- The number stays applicant-level and stable after the applicant already has a submitted application in the same College.
- Applicant Help is structured into phone, email and free-form notes/instructions; it is visible in the desktop sidebar and in the main application area on smaller screens, including Step-by-Step mode.

## Admission Confirmation / Approval — 2026-09-04
- Implemented canonical `admissions` transaction after Document Verification + Seat Allocation.
- Confirmation consumes only an existing `ALLOCATED` Seat Allocation and preserves exact Application, Choice, VERIFIED Document Verification, Merit Entry, Score, Intake and Selection Rule references.
- Backend transaction rejects unverified/non-submitted/inactive-allocation contexts; no capacity or Reservation logic is recalculated.
- Added stable Admission Number, optional approval note, CONFIRMED/REVOKED lifecycle, reasoned revocation and re-confirmation using the same Admission history row.
- Confirmed Admission now blocks Seat Allocation cancellation; revoked Admission does not silently release the seat and requires explicit Seat Allocation cancellation if release is intended.
- Added College-scoped view/confirm/revoke RBAC, sidebar/page integration, audit events and dependency-safe Test Data Cleanup support.
- Student creation/login promotion remains intentionally deferred to Student Enrollment / Lifecycle.
- Status: IMPLEMENTED — OWNER QA ACCEPTED.
- Governing decision: ADR 090.

## Batch Management — 2026-09-04
**IMPLEMENTED — OWNER QA ACCEPTED**

Admission Confirmation / Approval owner QA is accepted. The next College Academic Setup milestone, Batch Management, is implemented.

Implemented contract:
- `batches` is a child of one exact College Program Offering.
- Batch inherits College/Program/Curriculum/Academic Session from that Offering.
- New Batch starts INACTIVE.
- Activation requires ACTIVE College + ACTIVE Program Offering + ACTIVE Intake / Seat Capacity.
- Batch never recalculates Intake, Reservation, Seat Allocation or Admission decisions.
- RBAC: `college_batch.view/create/update/enable/disable` with exact College scope.
- Audit: create/update/activate/deactivate.
- Test Data Cleanup is Batch-aware.
- Owner QA parent-protection refinement: Intake and Program Offering deactivation controls are disabled while an ACTIVE Batch depends on them; backend dependency guards remain authoritative and return visible validation messages for stale/direct requests.

Batch Management Owner QA is accepted.

## Section Management — 2026-09-04
**IMPLEMENTED — OWNER QA REQUIRED**

Implemented contract:
- `sections` is a child of one exact Batch.
- Section inherits College/Program/Curriculum/Academic Session/Intake context through Batch.
- New Section starts INACTIVE; create/activation require ACTIVE Batch + Offering + Intake.
- Section never recalculates or consumes Intake/Reservation/Seat Allocation/Admission capacity.
- Section Management UI is Batch-centric and scalable and now reuses the existing Academic Structure expandable-row pattern for consistency: Batch-level Program/Session/Curriculum/Parent Program Intake context is shown once, a compact `Sections` disclosure row carries section counts, and Section rows render only when that Batch is expanded. The displayed Intake value is not Section capacity.
- `Add Section` is available inside each eligible Batch card and fixes that Batch during creation.
- RBAC: `college_section.view/create/update/enable/disable` at exact College scope.
- Audit: create/update/activate/deactivate.
- ACTIVE Section blocks parent Batch deactivation; backend remains authoritative.
- Test Data Cleanup is Section-aware and full reset deletes Sections before Batches.

Next after Owner QA: **College Academic Calendar -> Student Enrollment / Lifecycle**.


## Current next workflow — 2026-09-04 College Academic Calendar
Batch Management and Section Management Owner QA are accepted.

**College Academic Calendar is IMPLEMENTED — OWNER QA REQUIRED.**

Implemented contract:
- College adopts an existing same-University ACTIVE University Academic Calendar; it does not create an independent Session/calendar authority.
- University Academic Session and University events are inherited.
- College event override is possible only when the exact University event is ACTIVE and `allow_college_override = true`.
- Locked University events remain read-only at College level.
- Override reason is mandatory; override dates must remain inside the Academic Session.
- Disabling an override restores the University event as the effective event without modifying University data.
- College Calendar has College-scoped RBAC, audit logging and Test Data Cleanup support.

After Owner QA acceptance, implement **Student Enrollment / Lifecycle** consuming only CONFIRMED Admission and assigning the student into the operational Batch/Section structure.


## Curriculum Course Credit Treatment — 2026-09-04
- Status: IMPLEMENTED / OWNER QA REQUIRED.
- Curriculum Course / Paper Mapping now stores `COUNTABLE` or `NON_COUNTABLE` credit treatment; existing rows default to `COUNTABLE`.
- Numeric credit remains on Curriculum Slot. Non-countable is not zero-credit; it is a downstream exclusion from applicable earned / degree-credit totals.
- Course Category Group is independent. GPA inclusion/exclusion remains a separate rule.
- Student Enrollment / Lifecycle must consume this rule rather than assuming all mapped credits count.
- Governing decision: ADR 094.


## Curriculum Slot Credit Treatment — 2026-09-04

- `COUNTABLE` / `NON_COUNTABLE` Credit Treatment is owned by Curriculum Slot, not by individual Course / Paper Mapping.
- Existing Slots default to `COUNTABLE`; corrective migration promotes any earlier mapping-level `NON_COUNTABLE` value to its parent Slot and removes the mapping-level field.
- `NON_COUNTABLE` Slots keep their numeric credit visible but are excluded from Curriculum Required / Maximum credit totals and downstream degree-credit calculations.
- Slot Clone and Curriculum Amendment preserve Credit Treatment; Course mappings inherit it from the Slot.
- Course Category Group and GPA inclusion remain separate concepts.

## Fee Foundation — 2026-09-04
- Architecture corrected so Student Enrollment follows Fee Clearance rather than Admission Confirmation directly.
- University and College Fee Management foundation implemented.
- Added owned Fee Heads, Fee Structures and Fee Structure Items.
- University structures are scoped by Academic Session plus optional Program Template.
- College structures are scoped by exact College Program Offering; Program and Session are inherited.
- New Fee Heads and Fee Structures start INACTIVE; activation is guarded by active items/heads and duplicate active scope protection.
- Fee Items independently mark Mandatory, Enrollment Clearance Required and Installment Allowed.
- University and College RBAC plus audit events are implemented.
- Online/offline payment, Fee Demand, adjustments and Fee Clearance remain later milestones; no Student Enrollment starts until Fee Clearance is implemented.
- Status: IMPLEMENTED — OWNER QA REQUIRED.

## Fee Category Master — 2026-09-04
**IMPLEMENTED — OWNER QA REQUIRED**
- Fee Category is now configurable master data above Fee Head rather than a fixed enum/string.
- University categories are reusable across University fee setup and are inherited read-only by College Fee Management.
- Colleges may add local categories when required; category codes stay unique across the University fee domain.
- Existing common categories are seeded ACTIVE; new custom categories start INACTIVE.
- ACTIVE Fee Heads require ACTIVE categories; dependent ACTIVE Fee Heads protect category deactivation/editing.
- Program/Degree-specific fee applicability and amount remain in Fee Structure, not Fee Category.
- See ADR 096.


## University / College Fee Structure Applicability — 2026-09-04
- Added explicit University Fee Structure `college_applicability`: `MANDATORY` or `OPTIONAL`.
- University structure absence does not block College Fee Structure creation.
- `MANDATORY` University structures automatically apply to matching Colleges and remain read-only there; College-local additional structures remain allowed.
- `OPTIONAL` University structures may be explicitly adopted or ignored by each matching College. Adoption never copies University setup into College ownership.
- Matching requires an ACTIVE College Program Offering in the same Academic Session and, for Program-specific University structures, the same Program Template.
- The rule is generic for ADMISSION, ACADEMIC, EXAMINATION and OTHER and will be consumed later by Fee Demand generation.

### Fee Head inheritance correction — 2026-09-04
- University Fee Heads are inherited into College Fee Management as read-only master data.
- College Fee Structures can use ACTIVE University Fee Heads directly, without recreating them locally.
- College-local Fee Heads remain available for genuine College-specific charges.
- UI identifies Fee Head ownership and suppresses College mutation actions for inherited University heads.
- Backend accepts only same-University inherited heads or current-College local heads.
- See ADR 098.

### Fee Collection Basis (ADR 099)
Fee Structure now records collection timing independently from academic structure. Program Template/Curriculum remains academic source of truth. College Fee Structures derive term metadata from the exact Program Offering and specific-term selection is validated against its ACTIVE Curriculum Terms. Supported bases: ONE_TIME, PER_TERM, PER_ACADEMIC_YEAR, SPECIFIC_TERM, SPECIFIC_ACADEMIC_YEAR. Existing structures default to ONE_TIME pending review.

### Fee recurring-period amount resolution (ADR 100)
- `PER_TERM` and `PER_ACADEMIC_YEAR` Fee Items have a default amount plus optional per-period overrides.
- College period availability is sourced from ACTIVE Curriculum Terms on the exact College Program Offering; Program Template duration does not manufacture missing College periods.
- Semester academic-year billing groups use 2 actual Curriculum terms/year; trimester uses 3; year-based uses 1. Incomplete future groups are not exposed as eligible College billing years.
- Fee Demand is still deferred; it must later snapshot the resolved default/override amount against the exact academic period.

### Fee recurring-period applicability (ADR 101)
- Recurring Fee Items support explicit Not Applicable periods in addition to amount overrides.
- Not Applicable means no future Fee Demand line for that Fee Head/period; it is not a zero-value fee.
- College term choices are ACTIVE Curriculum Terms of the exact Program Offering.
- Academic-year choices are complete groups derived from those Curriculum term sequences.

### Fee Structure period-first configuration (ADR 102)
- University and College Fee Structure UI now follows `Fee Structure -> Billing Periods -> Fee Items`.
- Recurring charges are configured directly inside each Semester/Term or Academic Year instead of exposing the storage-level default/override model to users.
- College billing periods continue to come only from ACTIVE Curriculum Terms of the exact Program Offering; complete Academic Year groups are derived from those terms.
- University period templates are derived from the selected Program Template because University structures have no exact College Curriculum; College-side operational use must still respect the Offering Curriculum.
- The same Fee Head may have different amounts by period or be absent from a period without fake zero/negative amounts.
- No new database table is introduced; existing period amount/exclusion children remain the normalized storage.

### Fee Structure configured totals / one-time admission (ADR 103)
- University and College Fee Structure cards show a Configured Total calculated from ACTIVE/applicable effective Fee Item amounts across Billing Periods.
- Recurring period exclusions are not counted; period-specific amounts are respected.
- `ADMISSION + ONE_TIME` represents a single one-time admission charge period. Admission Fee remains optional and does not by itself define enrollment clearance.

### Period-first Fee Item applicability UX — ADR 104
- University and College Fee Item dialogs no longer expose `Applicable in this billing period`.
- User-facing rule is now simple: a Fee Item configured inside a Billing Period is applicable there; if a charge should not apply to another period, it is not added there.
- Period-specific amount remains entered directly inside that Billing Period.
- Period amount/exclusion records remain normalized backend storage only and must not define the visible workflow.
- No migration or RBAC change is introduced by this UI correction.

## 2026-09-05 — University Fee period consistency correction
- ADR 105 accepted: University recurring/specific academic-period Fee Structures now require an exact ACTIVE + APPROVED Curriculum.
- Added nullable `fee_structures.curriculum_id`; ONE_TIME remains curriculum-independent.
- University Billing Periods now come from real ACTIVE Curriculum Terms, not synthetic Program Template duration slots.
- College remains sourced from its Program Offering's exact Curriculum.
- University→College applicability additionally matches exact Curriculum when the University structure is curriculum-scoped.
- Existing pre-fix recurring University structures must be edited and assigned a Curriculum before activation/use.


### University Fee Curriculum selector current-version guard — 2026-09-05
- University Fee recurring/specific period configuration now exposes only the **current ACTIVE + APPROVED Curriculum version**.
- Currentness reuses the canonical Curriculum amendment-chain rule: no approved successor exists.
- Superseded/previous approved Curriculum versions are hidden from the selector and rejected server-side.
- Existing ACTIVE historical Fee Structures remain bound to their original Curriculum; they are never silently migrated to a newer amendment.
- ADR 106 records this rule.

## Fee period-specific charge policy — 2026-09-05
Recurring Fee Structures now store billing-period-specific policy in `fee_structure_item_period_settings`. Amount, Mandatory, Enrollment Clearance Required, Installment Allowed, display order and ACTIVE/INACTIVE status are treated as properties of the charge inside the exact Semester/Academic Year. `ONE_TIME` structures continue to use the parent Fee Structure Item fields directly. UI and configured-total calculations use the effective period-specific policy. See ADR 107.

### Fee Foundation — University policy visibility and local overlap guard (2026-09-05)
- Applicable University Fee Structures are read-only on College Fee Management and expose `View Structure` before/after adoption.
- College may add local Fee Structures to the same Program Offering for additional charges.
- An effective University structure (MANDATORY or adopted OPTIONAL) blocks a College-local charge when the same Fee Head overlaps the same underlying academic billing coverage and purpose.
- OPTIONAL structures not adopted are not effective and therefore do not block local configuration.

### Fee effectiveness overlap lifecycle guard — 2026-09-05
- ADR 110 closes the lifecycle bypass discovered during University→College applicability QA.
- Item-save validation is no longer the only protection: conflict checks also run whenever a College structure is activated, an OPTIONAL University structure is adopted, or a University structure is activated/re-activated for Colleges.
- An INACTIVE College structure may remain configured beside an effective University policy, but it cannot activate while the same Fee Head overlaps the same billing coverage.
- If a College structure is already ACTIVE, adopting a conflicting OPTIONAL University structure is blocked.
- MANDATORY University activation and OPTIONAL re-activation with existing adoption are also protected.
- No migration/RBAC change. Owner QA required.

### Fee Item removal (ADR 111)
- Fee Items now expose a remove action while their Fee Structure is INACTIVE.
- In recurring structures, remove is billing-period scoped: the selected period is excluded and its amount/policy rows are cleared; other periods remain intact.
- Removing the final applicable recurring period deletes the shared Fee Item. ONE_TIME/specific structures delete the item directly.
- University-owned structures remain read-only from College scope.

## Applicable Fee Demand — 2026-09-05
- Implemented admission-level Fee Demand generation after CONFIRMED Admission and before Payment/Fee Clearance.
- Resolves effective MANDATORY / OPTIONAL+ADOPTED University structures plus ACTIVE College structures for the exact Program Offering, Session and Curriculum.
- Period 1 includes ONE_TIME charges; recurring/specific charges use exact billing-period configuration.
- Demand Items snapshot amount + Mandatory + Enrollment Clearance Required + Installment Allowed so later Fee Structure changes do not rewrite historical liabilities.
- Added totals for total demand, mandatory amount, enrollment-clearance amount and outstanding amount; payment/adjustment columns are reserved for the next milestone.
- Added College Fee Demands page, view/generate/cancel RBAC and unpaid cancellation with retained history.
- Status: IMPLEMENTED — OWNER QA REQUIRED. Governing decision: ADR 112.

## Applicable Fee Demand — 2026-09-05
- Implemented admission-level Fee Demand generation after CONFIRMED Admission and before Payment/Fee Clearance.
- Resolves effective MANDATORY / OPTIONAL+ADOPTED University structures plus ACTIVE College structures for the exact Program Offering, Session and Curriculum.
- Period 1 includes ONE_TIME charges; recurring/specific charges use exact billing-period configuration.
- Demand Items snapshot amount + Mandatory + Enrollment Clearance Required + Installment Allowed so later Fee Structure changes do not rewrite historical liabilities.
- Added totals for total demand, mandatory amount, enrollment-clearance amount and outstanding amount; payment/adjustment columns are reserved for the next milestone.
- Added College Fee Demands page, view/generate/cancel RBAC and unpaid cancellation with retained history.
- Status: IMPLEMENTED — OWNER QA REQUIRED. Governing decision: ADR 112.

## Fee Demand workflow — ADR 115
- Initial Fee Demand is now admission-driven: a new/reconfirmed `CONFIRMED` Admission automatically attempts Billing Period 1 demand generation.
- No applicable active fee item means no demand is required; Admission Confirmation itself remains valid.
- Existing confirmed admissions without an active Period 1 demand are exposed only in a Manual Recovery control.
- Demand snapshot continues to preserve effective amount, Mandatory, Enrollment Clearance Required and Installment Allowed per item/period.
- Fee Demand page resolves and displays the applicable University Academic Policy automatically from the selected ACTIVE Program Offering.
- Policy specificity: Curriculum > Program Template > Degree Level > University; same University/Session, ACTIVE, APPROVED, current version and effective dates are required.
- Same-specificity policy ambiguity blocks progression/bulk processing.
- Later-period bulk generation is not yet executable because Student Enrollment / Student Academic Progression authoritative persistence has not been implemented. Fee Management must consume that future eligibility state rather than duplicate Academic Policy calculation.
- Admission revocation auto-cancels untouched demands; any paid/adjusted demand blocks revocation pending financial reversal/settlement.
- `fee_demands.generation_mode` identifies ADMISSION_AUTO, MANUAL_RECOVERY and future BULK_PERIOD demand provenance.

### Fee Demand Test Data Cleanup — implemented 2026-09-05
- Test Data Cleanup → Fee Management now exposes Fee Demands before Fee Structures/Heads/Categories.
- Cleanable demand deletes `fee_demand_items` then `fee_demands` in one transaction and records audit event `TEST_FEE_DEMAND_CLEANED`.
- Demand cleanup is blocked once payment/adjustment or downstream financial activity exists.
- Admission cleanup is blocked while a Fee Demand references the Admission.
- Full Academic Test Reset includes Fee Demand Items and Fee Demands before Admission deletion.
- Canonical decision: ADR 116.

## 2026-09-05 — Fee billing-context integrity lock (ADR 117)
- Fixed unsafe reinterpretation when an INACTIVE Fee Structure with existing Fee Items changed collection basis (for example PER_ACADEMIC_YEAR → PER_TERM).
- Fee billing context is now immutable while Fee Items exist. Remove Fee Items first before changing collection basis/specific period/session/program offering/program template/curriculum.
- UI locks Fee Collection Basis while configured items exist and shows the reason.
- Backend independently blocks billing-context changes with configured items; no silent amount/applicability conversion is performed.

## Fee Demand — Fee Setup-driven bulk workflow (ADR 118)
- Admission Confirmation demand is purpose-aware: ADMISSION charges + first-period ACADEMIC Enrollment-Clearance charges only.
- Fee Demand now derives Program Offering bulk contexts directly from effective Fee Setup purpose + collection basis + exact Curriculum periods.
- First Academic period supports offering-level bulk generation against the CONFIRMED admission cohort; previously demanded source items are skipped/protected.
- Later Academic periods are intentionally blocked until authoritative Student Enrollment + Academic Progression persistence is available; the existing University Academic Policy resolver is the authority chain, not Fee Management.
- EXAMINATION and OTHER bulk remain blocked until their own authoritative eligibility/cohort sources exist.
- Existing generated demands remain immutable snapshots even if future Fee Setup changes.

### Fee Demand refundability snapshot
- Fee Demand Items now snapshot Fee Head `is_refundable` at generation time (ADR 119).
- Downstream refund/reversal logic must use the demand snapshot, not re-read a mutable Fee Head as historical truth.

### Fee Demand — Bulk + Individual generation (ADR 120)
- Routine period demand now supports `Bulk Cohort` and `Individual` from the same Program Offering / Purpose / Fee Setup billing context.
- Individual mode is not an override path: it uses the exact same eligibility gate, applicable structures, period snapshots and duplicate-source-item protection as Bulk.
- First Academic period currently selects from CONFIRMED admissions because Student Enrollment/Academic Progression is not yet authoritative. Semester/Year 2+ remains blocked in both modes until progression output exists.
- `INDIVIDUAL_PERIOD` records individual generation provenance; existing `college_fee_demand.generate` permission covers both modes.

### Fee Demand register UX — grouped and collapsed (ADR 121)
- The College Fee Demand history is now candidate-centric: demands are grouped under one Admission/Application record instead of one full card per demand.
- Candidate groups and child demand details are both closed by default to keep the page compact as Semester/Year/Exam demand history grows.
- Search covers Application No., Admission No., candidate/student name, demand number and billing-period/context labels; the visible register is paginated 10 admissions at a time.
- Aggregate Total, Mandatory, Enrollment Clearance and Outstanding amounts are display summaries; every child Fee Demand remains an independent immutable snapshot.
- Manual Recovery is no longer exposed on the routine Fee Demand page. Recovery backend behavior is retained only for exceptional maintenance/legacy handling.

### Fee Demand individual selector scalability (ADR 122)
- Individual Fee Demand candidate/student selection is searchable by name, Application No. and Admission No.
- Search is server-side, scoped to the selected ACTIVE College Program Offering and CONFIRMED cohort, debounced, and capped at 30 results.
- Fee Demand index no longer needs to preload every confirmed admission for every offering.

### Project-wide mutation outcome feedback (ADR 123)
- Inertia now shares the server `toast` flash globally and the existing Sonner/Toaster hook renders it through the project theme.
- User-triggered protected no-ops must not be silent. Fee Demand Individual duplicate protection now returns an informational message instead of only rejecting the duplicate internally.
- Fee Demand Bulk generation reports clear generated/skipped/error outcomes; a fully skipped rerun reports `no new demands generated` as information.
- This visible-outcome rule applies to future create/update/delete/activate/adopt/generate/approve/cleanup workflows; do not add a second page-specific notification system.


### Global toast context safety (ADR 124)
- Global Sonner Toaster is mounted from the Inertia `withApp` shell, outside page context.
- `useFlashToast()` uses `router.on('flash', ...)`; it must not use `usePage()`.
- Visible mutation feedback from ADR 123 is retained without risking a blank application shell.

### Mutation feedback delivery — AppLayout Inertia-context bridge (ADR 125)
- Global Sonner Toaster is presentation-only and does not call `usePage()`.
- `AppLayout` invokes `useFlashToast()` inside the Inertia page context.
- `useFlashToast()` renders shared `flash.toast` success/info/warning/error outcomes.
- If a mutation returns validation errors without an explicit toast, the first validation error is surfaced as an error toast.
- This is the canonical notification path for future ERP mutation feedback; protected no-op actions must not remain silent.

### Fee Scholarship / Benefit Foundation — IMPLEMENTED, QA PENDING (2026-09-07)
University/College Scholarship, Concession and Waiver policy setup is implemented. Schemes target Fee Heads, may use Reservation Category as an eligibility input, support Fixed/Percentage benefits and approval mode, and remain separate from immutable Fee Demand gross amounts. Candidate-level assignment/sanction and Fee Demand adjustment are implemented under ADR 130 and subsequent Student Benefits ADRs.

## Scholarship / Benefits Test Data Cleanup — 2026-09-07
- Test Data Cleanup → Fee Management now includes `Scholarship / Benefits` for both University-owned and College-owned schemes.
- Per-scheme cleanup removes only Fee Head mappings, Reservation Category mappings and the scheme itself; shared Fee Heads and Reservation Categories are preserved.
- Future student/financial benefit references block scheme cleanup when present.
- Full Academic Test Reset counts and removes scholarship scheme mappings + schemes in dependency-safe order.
- Audit event: `TEST_FEE_SCHOLARSHIP_SCHEME_CLEANED`.
- Governing decision: ADR 127. Status: IMPLEMENTED — OWNER QA REQUIRED.

### Scholarship Session Context Eligibility — QA Pending (ADR 128)
- University Scholarship/Benefits session selector now shows only sessions with a current ACTIVE + APPROVED Curriculum.
- College selector now shows only sessions represented by an ACTIVE College Program Offering whose Curriculum is current ACTIVE + APPROVED.
- Eligible `is_current` Academic Session is sorted first and selected by default.
- College Program Offering options remain session-filtered.
- Backend validates the same academic-context rules to prevent stale/tampered submissions.

### Scholarship / Benefits action-control consistency — 2026-09-07
- Scholarship scheme list actions follow the project-standard icon + text Button pattern: Pencil/Edit, Power/Activate, PowerOff/Deactivate.
- Presentation-only change; RBAC and lifecycle rules remain unchanged. See ADR 129.


## ADR 130 — Student Benefit Assignment / Sanction / Demand Adjustment
Implemented College-side operational Scholarship / Concession / Waiver consumption. ACTIVE University + College schemes are resolved against a selected Fee Demand; reservation eligibility consumes Admission seat-allocation category snapshots; MANUAL schemes create PENDING assignments, AUTOMATIC schemes auto-sanction through an auditable record; approved sanction posts item-scoped adjustment without mutating original demand/item amounts. Student Benefit RBAC and Test Data Cleanup are included.

### Candidate Reservation Category authority (ADR 132)
Implemented: Admission Form mapping can identify the candidate category field; Seat Allocation snapshots mapped/manual candidate category separately from the physical reservation seat; Student Benefits consumes candidate category only.

### Bulk Student Benefit controlled selection (ADR 137) — IMPLEMENTED, QA PENDING (2026-09-08)
- Student Benefits now has Individual and Bulk Assignment modes.
- Bulk mode resolves ACTIVE scheme-scoped Fee Demands and groups eligible students as a collapsed `Degree Level → Degree → Discipline → Student` tree.
- Degree Level, Degree and Discipline are explicitly labelled to keep large cohorts readable and easy to differentiate.
- Parent/group selection is convenience only; the operator can choose any subset, which preserves institution discretion for merit/open schemes.
- Student rows show candidate/application/admission/demand, candidate category snapshot, eligible base and calculated benefit.
- Existing PENDING/APPROVED duplicate scheme assignments are excluded; every selected demand is revalidated server-side before assignment.
- MANUAL/AUTOMATIC sanction behavior and immutable gross Fee Demand rules continue to reuse ADR 130 logic.

### Seat Allocation confirmed-admission guard restored + toast consistency (ADR 133)
- `Cancel Allocation` is disabled when the linked Admission is `CONFIRMED`; backend cancellation independently enforces the same rule.
- `REVOKED` Admission does not permanently block explicit seat cancellation/release.
- ADR 132 Candidate Reservation Category mapping/manual fallback remains intact.
- Seat Allocation mutation outcomes use the canonical ADR 125 Sonner toast path; page-specific success/error alerts are not used.

### 2026-09-07 — Seat Allocation category + merit guard (ADR 135)
- Candidate category selection is restricted to ACTIVE VERTICAL categories; Horizontal quota stays separate.
- Allocation dialog recommends the candidate's own reserved bucket while available, then OPEN as capacity fallback.
- Cross-category reserved-seat allocation is blocked in UI and backend.
- General / Unreserved cannot consume a reserved physical bucket.
- OPEN manual allocation cannot bypass a higher-ranked VERIFIED + ELIGIBLE unallocated roster candidate.
- Reserved allocation protects merit order within the same mapped candidate category.

### 2026-09-07 — Application candidate-category auto-resolution (ADR 136)
- Seat Allocation now resolves Candidate Reservation Category directly from the submitted application value of the system-mapped `CANDIDATE_RESERVATION_CATEGORY` field before using the legacy cycle mapping fallback.
- Valid mapped application category is auto-selected/locked; manual confirmation is shown only when no valid application category can be resolved.
- Candidate category runtime options are synthetic `General / Unreserved` plus ACTIVE VERTICAL University categories only; Horizontal categories are excluded.
- General candidate classification is not a physical reservation quota: Intake keeps `OPEN` as the category-neutral merit pool.

### Student Benefits — ADR 137 QA correction (2026-09-08)
- Bulk Assignment no longer renders stray `0`/`000` placeholders when no scheme is selected; numeric render guards use explicit boolean checks.
- ACTIVE bulk benefit schemes are delivered in the initial Inertia page payload, so the Benefit Scheme dropdown is populated immediately from the same server-side scheme resolver used by Student Benefits.
- When no ACTIVE University/College scheme exists for the College, the UI shows an explicit empty-state message instead of silently presenting an empty dropdown.

### ADR 138 — Student Benefit bulk candidate resolution + removal (2026-09-08)
- Bulk Student Benefit hierarchy remains Degree Level → Degree → Discipline → Student.
- Discipline is resolved from `college_admission_application_academic_preferences.discipline_id`; Program Template no longer has a single `discipline_id` after the many-to-many discipline refactor.
- Bulk endpoint failures now display a visible error message rather than a misleading empty list.
- Student Benefit Register supports `Remove` for mistaken active benefits when user has cancel permission.
- PENDING removal has no financial effect. APPROVED removal reverses only that benefit's sanctioned Fee Demand adjustment and recalculates demand outstanding/status inside a transaction.
- Removed records remain audit-visible as `CANCELLED`; they are not hard-deleted.
- Status: IMPLEMENTED — FULL STUDENT BENEFITS QA PENDING.

### ADR 139 — Student Benefit selection clearing + eligibility diagnostics (2026-09-08)
- Individual demand selection is explicitly staged UI context and can be cleared without deleting or changing any Student, Admission or Fee Demand record.
- Typing a new Individual search after a selection clears stale scheme/demand state automatically.
- `Not eligible` is no longer opaque: Individual mode exposes each scheme's exact eligibility reason, including candidate Reservation Category mismatch, missing applicable Fee Head, or no remaining eligible fee amount.
- Bulk mode continues to load only eligible candidates for controlled selection, but its summary now carries aggregated ineligibility reasons so a zero-result cohort is diagnosable rather than ambiguous.
- Eligibility business rules are unchanged and shared between Individual and Bulk flows; Bulk still performs final per-demand revalidation before assignment.
- Status: IMPLEMENTED — OWNER QA REQUIRED.

### Student Benefits Individual duplicate-state guard — IMPLEMENTED / QA REQUIRED (ADR 140, 2026-09-08)
- Individual `Applicable scheme` resolution now includes active benefit assignment state for the selected Fee Demand.
- Same Fee Demand + Scheme with `PENDING` or `APPROVED` status is shown disabled as `Already assigned — PENDING/APPROVED` and cannot be submitted again.
- The Fee Demand remains available in student/demand search so other independently eligible schemes can still be considered.
- `REJECTED` and `CANCELLED` records are historical/auditable and do not count as active duplicates.
- Bulk and Individual flows now use the same active-duplicate rule; backend `assign()` duplicate validation remains the final concurrency-safe guard.

### Fee Demand — approved Student Benefit visibility (ADR 141, 2026-09-08)
- Expanded Fee Demand details expose Gross Demand, Paid, active approved Scholarship/Concession/Waiver adjustment, and Outstanding separately.
- Approved benefit adjustments are auditable in-place by scheme name/code and sanctioned amount, with fee-item-level benefit amounts shown on affected demand items.
- Existing financial invariant remains: Outstanding = Gross Demand - Paid - Approved Adjustments; Gross Demand is never rewritten by Student Benefits.

## 2026-09-08 — Student Benefits QA PASS / Installment Scheduling implemented for QA
Student Benefits is QA PASSED. Confirmed: demand-specific eligibility, controlled bulk selection, MANUAL PENDING flow, approval financial adjustment, active duplicate protection, Fee Demand benefit audit visibility, approved-benefit reversal, and same-scheme re-assignment after CANCELLED.

Fee Installment execution (ADR 142) is now implemented and **QA PENDING**. Existing Fee Structure / period-level `Installment Allowed` remains authoritative; schedules execute only against eligible Fee Demand Items and never rewrite gross liability. Schedule amount is based on the current net item payable after APPROVED Student Benefits. Test Data Cleanup includes Installment Schedules.

After Installment QA PASS, continue remaining Fee Phase before Student Lifecycle. Late Fine Rules are the next policy area to freeze/implement, followed by Payment Collection + Allocation (including pre-integrated online gateways), Student Fee Ledger, generic Adjustment/Reversal/Refund, Fee Clearance, then Enrollment/Student Lifecycle consuming Fee Clearance.

### 2026-09-08 — Installment Scheduling bulk/common execution added (ADR 142 extension)
Installment Scheduling remains **QA PENDING**, now with both common/bulk and individual execution. Bulk uses the selected Program Offering + billing context, previews installment-enabled Fee Heads and eligible student demand items, groups students by Admission Academic Preference discipline, and supports controlled selection. A 100% percentage schedule is converted to each student's current net payable after APPROVED benefits. Existing schedules can be replaced only before collection; individual scheduling remains the exception path. Gross demand is unchanged.

### Installment Scheduling QA refinement — 2026-09-08
- Bulk common schedule editor is positioned before student groups.
- Discipline groups default collapsed; student rows load visually only on explicit Expand.
- Bulk selection and eligibility logic are unchanged; Installment Scheduling remains in QA.

### 2026-09-08 — Student Benefit / Installment integration (ADR 143) — IMPLEMENTED, QA PENDING
Installment Scheduling remains **QA PENDING**. Student Benefit approval is now installment-aware: if the affected student's Fee Demand Item already has an ACTIVE schedule, a MANUAL approver chooses Proportional, Next Unpaid First, or Custom distribution. AUTOMATIC benefits use Proportional. Only the affected student's schedule changes; gross demand/item amounts remain immutable. Benefit removal reverses/recalculates the installment effect with audit snapshots. Installment rows now reserve `paid_amount` and allocation metadata for the upcoming Payment Collection + Allocation module, where already-paid installment history must remain immutable.

ADR 143 is part of the Installment Scheduling QA gate; do not mark Installment Scheduling PASS until benefit-after-installment approval, custom validation, student isolation, and approved-benefit reversal are verified.

## 2026-09-08 — Student Benefits Bulk Route Regression QA Hotfix
- QA exposed a routing regression on Student Benefits Bulk Assignment: the frontend requested `GET /college/{college}/fee-student-benefits/bulk-candidates`, but the current `routes/web.php` no longer registered the previously implemented bulk Student Benefit endpoints.
- Restored the three existing controller routes: `bulk-schemes`, `bulk-candidates`, and bulk `POST` assignment. No eligibility, benefit, installment, or financial calculation rules were changed.
- This is a regression restoration for ADR 137+ behavior and is required before continuing ADR 143 benefit-after-installment QA.
- Student Benefits remains previously QA-passed functionally; current combined Installment/Benefit integration remains QA pending until bulk loading and benefit-after-installment scenarios are reverified.



### 2026-09-09 — ADR 144 Student operational register scalability + lifecycle-aware context
- Student Benefit Register changed from up-to-250 fully expanded cards to a compact server-paginated register with a View/detail workflow.
- Current Academic Session is automatically selected from the canonical `academic_sessions.is_current` flag; historical Session remains selectable.
- Register filters: Programme Offering, Billing Period, Scheme, Status and Student/Application/Admission/Demand/Scheme search.
- Batch is intentionally not used in Student Benefits because canonical Student Academic Lifecycle assigns Batch during Enrollment, after Admission Confirmation.
- Established the same lifecycle-aware Session-first filtering standard for future student operational screens; Batch is used only after the relevant lifecycle stage guarantees an assignment.
- ADR 143 UI wording changed from `Reduce next unpaid first` to `Apply to Next Installment First`; internal enum remains `NEXT_UNPAID_FIRST`.
- Custom Distribution now shows New Payable, Allocated and Remaining while entering post-benefit installment amounts.
- QA pending for the new register/filter/pagination presentation; financial business rules are unchanged.

### 2026-09-09 — ADR 145 Fee Demand register scalability — IMPLEMENTED, QA PENDING
Fee Demand now follows the project-wide compact operational-register standard. The register is server-paginated by Admission/Application (25/50/100), Current Academic Session is auto-selected from the canonical session flag, and optional Programme Offering / Status / Search filters narrow the dataset. Giant admission cards were replaced by compact rows; demand, fee-head, benefit and installment detail is loaded/rendered only for the visible page and opened on demand. Batch remains lifecycle-aware and is not required for confirmed-admission fee demand. Financial and generation rules are unchanged. QA is required before marking this presentation change PASS.

### 2026-09-09 — ADR 146 Student Benefit grouped register refinement
- Student Benefit Register now groups all matching benefits for the same Fee Demand/admission context into one compact parent row instead of repeating the same student/application for every scheme.
- Parent rows show Discipline and current Age derived from the admission application, plus benefit count and aggregate benefit amount.
- `Benefits (N)` expands scheme-level child rows; individual `View` preserves the existing approval/removal/installment-adjustment workflow.
- Added optional Discipline filter based on saved Admission Academic Preference. Current Session remains default; Batch remains intentionally excluded pre-enrollment.
- Pagination now counts demand/admission benefit groups rather than raw benefit records.

### 2026-09-09 — ADR 147 Fee Demand discipline/age context
- Fee Demand compact admission-group register now shows Discipline, Age and Programme at parent level.
- Added optional Discipline filter constrained by Session/Programme Offering and preserved across search/status/pagination.
- Discipline/search context comes from Admission Academic Preference; no Batch dependency was introduced before Enrollment.
- Existing financial calculations, demand details, benefits, installments and cancellation behavior remain unchanged.

## Test Data Cleanup bulk module operations — 2026-09-09 / ADR 148
- Test Data Cleanup now supports checkbox selection on cleanable rows instead of requiring record-by-record cleanup only.
- Header Select All selects every cleanable record in the current visible scope; blocked/protected rows remain disabled and visible.
- `Clean Selected (N)` cleans only checked records.
- `Clean All Cleanable (N)` cleans every currently cleanable record in the active module even when an optional display filter is active; protected/dependency-blocked records are preserved.
- Bulk cleanup is available for regular maintenance entities, Curriculum and Academic Policies. Academic Policy cleanup remains complete-version-chain aware.
- Bulk confirmation uses one module-level phrase (`CLEAN-<MODULE>-TEST-DATA`) while individual cleanup actions retain their existing exact-record confirmation codes.
- This is now the required Test Data Cleanup UI pattern for future multi-record cleanup modules unless a specific ADR documents a stricter exception.
- No schema migration is required; QA is pending.

## 2026-09-09 — ADR 149 System Reservation Category auto-consumption
- Removed the redundant Candidate Reservation Category field selector from College `Map Form for Application Entry`.
- The Admission Form system-purpose field `CANDIDATE_RESERVATION_CATEGORY`, already backed by the University Reservation Category Master, is now the authoritative source and is discovered automatically when a form is mapped.
- Seat Allocation resolves the submitted system field first and labels its source `SYSTEM_FORM_FIELD`; older saved mappings remain a `LEGACY_FORM_MAPPING` fallback only.
- If no system Reservation Category is present/captured, authorized manual confirmation remains available at Seat Allocation.
- Candidate reservation identity remains separate from the physical seat category used for reservation consumption.
- Existing mapping schema is retained for backward compatibility; no migration is required. QA pending.

## 2026-09-09 — ADR 150 Direct Admission downstream gates — IMPLEMENTED, QA PENDING
- Fixed the Direct Admission workflow gap: a SUBMITTED Direct application no longer depends on a generated Merit / Roster to reach Document Verification or Seat Allocation.
- Canonical path is now `DIRECT SUBMITTED → Document Verification → Seat Allocation → Admission Confirmation → Fee Demand / Student Benefits`.
- Direct Admission bypasses Eligibility/Score/Interview/Merit/Selection Rule processing only; Document Verification, Intake, reservation, physical seat capacity and Admission Confirmation remain mandatory.
- Direct submission now locks one unambiguous Intake/seat-bucket processing choice from saved Program/Discipline/Specialization context with no Selection Rule. Existing pre-ADR-150 Direct applications are repaired when Document Verification is finalized VERIFIED.
- Seat Allocation supports Direct rows without fake Merit/Score/Rank values; nullable Direct-only bypass columns were enabled on `college_admission_seat_allocations`.
- Admission Confirmation displays Direct rows explicitly as DIRECT ADMISSION and continues into the same Fee Demand / Student Benefit financial workflow after confirmation.
- Test Data Cleanup must preserve downstream-first dependency ordering for Direct QA records; ADR 148 bulk cleanup does not bypass those guards.

## ADR 151 — Admission revoke finance cascade + independent installment execution contexts (2026-09-09)
- Admission Confirmation revoke now makes all linked unposted Fee Demand activity non-operational without deleting audit history: linked non-cancelled demands are CANCELLED, ACTIVE installment schedules are CANCELLED, and PENDING Student Benefits are CANCELLED. Payment or approved-adjustment activity continues to block revoke until reversal/settlement.
- Fee Demand Register defaults to Active / Non-cancelled; CANCELLED history remains explicitly filterable.
- Bulk Installment execution no longer reuses Fee Demand generation contexts. `installment_contexts` are derived from existing active installment-enabled Fee Demand Items, so automatically-created `ADMISSION_INITIAL / MIXED` demands are valid Bulk Installment scopes.
- Installment Scheduling remains QA PENDING until Admission Initial bulk load and revoke-cascade QA pass.

## ADR 152 — Bulk Installment Scope Normalization
- QA regression fixed where installment scope discovery could show a cohort but `Load Eligible Students` returned no rows for older/null-context Fee Demands.
- Scope discovery and candidate loading now share the same normalized purpose, billing-basis and period rules.
- Legacy Fee Demands created before context columns were populated remain supported.
- Admission Initial auto-demands remain supported when their Fee Demand Item snapshot has `installment_allowed = true`.
- No migration required.

## ADR 154 — Bulk Installment route regression fix
- Bulk Installment preview/store controller actions are now explicitly registered in `routes/web.php`.
- `GET /college/{college}/fee-installments/bulk-preview` loads eligible candidates.
- `POST /college/{college}/fee-installments/bulk` applies the common schedule.
- This fixes the route-not-found regression without changing installment business logic.

### ADR 155 — Student Benefit bulk route regression fixed (2026-09-09)
- Restored `bulk-schemes`, `bulk-candidates`, and bulk assignment POST routes for Student Benefits.
- Preserved ADR 154 Bulk Installment routes in the same `routes/web.php`.
- No Student Benefit eligibility/business logic change; this is route-contract restoration only.
- Shared-route-file rule: future replacements must preserve already-implemented module routes.

### ADR 156 — Student Benefit register admission/application grouping — IMPLEMENTED, QA PENDING
- Student Benefit Register now presents one parent row per Admission/Application across multiple Fee Demands/billing periods.
- Benefits created through Individual and Bulk Assignment are grouped together when they belong to the same admission.
- Parent row summarizes visible billing periods, distinct demand count, benefit count, aggregate benefit value and combined status.
- Child rows retain Scheme, exact Fee Demand, Billing Period, assignment source, status and full approval/removal/installment-adjustment detail.
- Pagination counts admission/application groups rather than Fee Demand groups.
- No financial posting, eligibility, installment-adjustment or lifecycle business rule changed; Batch remains excluded before Enrollment.

### Student Benefit Register compact parent display — ADR 157
- Admission/application grouping from ADR 156 remains unchanged.
- Parent register row now shows Programme only; billing-period and demand-count summary text was removed from the parent row.
- Exact Demand / Period and source remain available in expanded Benefits child rows.
- No financial, eligibility, approval, installment-adjustment, or grouping logic changed.


## 2026-09-09 — ADR 159 Late Fine UI consistency hotfix
- Removed nested `AppLayout` from Late Fine / Penalty page.
- Late Fine now follows the existing Fee Management page shell and compact spacing.
- No external CSS added; existing project components and project DatePicker remain in use.
- No business logic, RBAC, migration, cleanup, or calculation behavior changed.

## 2026-09-09 — ADR 160 Fee Billing Period Standard Due Date — IMPLEMENTED, QA PENDING
- Fee Setup now stores a Standard Due Date for every effective Fee Head/Billing Period charge rather than inferring payment timing from academic period start/end.
- Recurring PER_TERM / PER_ACADEMIC_YEAR charges store Due Date per period in `fee_structure_item_period_settings`; one-time/specific-period charges store it on `fee_structure_items`.
- Fee Demand Items snapshot the applicable Standard Due Date in `fee_demand_items.due_date`, so later setup changes do not rewrite historical demand timing.
- If no installment schedule exists, this snapshot is the future authoritative due date for Late Fine. If installments exist, installment due dates control installment payment timing.
- Structure activation is blocked when an ACTIVE applicable Fee Head/Billing Period has no Standard Due Date. Existing legacy active structures are not auto-backfilled with invented dates and require explicit remediation; new Fee Demand generation also blocks until the missing Due Date is fixed.
- Project DatePicker and existing Fee Management UI components are used; no external styling added.
- ADR 158 Late Fine remains QA PENDING and must be revised after ADR 160 QA to support both installment and non-installment due sources.

## ADR 161 — Curriculum-linked Academic Periods (QA pending)
Academic Calendar now reuses existing Curriculum Terms rather than defining duplicate Semester/Year masters. University assigns session-specific Start/End dates per Curriculum Term; events may optionally belong to that period. Fee Standard Due Dates are validated against these effective boundaries. Existing general events remain valid. ADR 160 is now dependent on ADR 161 period boundaries for term/year charges. Late Fine QA remains deferred.


### ADR 162 — ADR 161 migration recovery
ADR 161 migration is now safe to retry when MySQL retained `academic_calendar_term_periods` after a failed/partial migration. Continue Academic Calendar period QA after migration succeeds.

## Academic Calendar — ADR 163 selector UX — 2026-09-09
- **IMPLEMENTED / QA PENDING.** `Add Academic Period` now uses searchable Curriculum -> Academic Period dependent selection rather than one long combined Curriculum-Term dropdown.
- Internal revision-chain codes are hidden from user-facing labels; exact Curriculum Term identity remains authoritative and is persisted unchanged.
- Existing Academic Calendar period/date governance and ADR 161/162 behavior are unchanged.

## ADR 164 — Curriculum effective-window linkage (2026-09-09)
Academic period and recurring Fee Due Date configuration now form one enforced chain: Academic Session → current approved Curriculum → existing Curriculum Term → Academic Calendar Term Period → Fee Billing Period → Standard Due Date. Academic Period dates must remain inside the intersection of Session dates and Curriculum Effective From/To (blank Curriculum bounds fall back to Session bounds). Fee Setup resolves those same active Calendar Term Periods and rejects due dates when the applicable period is missing, invalid, or outside Curriculum validity. The UI and backend use the same effective bounds. No new Semester/Year master is created. ONE_TIME/Admission Initial remains a deliberate non-term exception. QA is pending; Late Fine remains deferred until this integration passes.

### ADR 165 — Test Data Cleanup bulk route regression
- Test Data Cleanup bulk operations use `POST /admin/system-maintenance/test-data-cleanup/bulk-clean`.
- Route is restored and must be preserved in all future `routes/web.php` replacements.
- Controller/dependency guards remain authoritative; this change is route-only.
- QA pending: Clean Selected, Clean All Cleanable, and individual Clean spot-check.

### ADR 166 — Admission Form Academic Applicability + Candidate Profile Preview
Academic Applicability is now a multi-value server-authoritative rule. University field setup supports multiple Degree Levels, Degrees, Programs and current approved Curricula; College field setup additionally supports Program Offerings and Admission Cycles. Same-dimension selections are OR and configured dimensions are AND. The existing field-scope table is reused with no schema migration. Applicant field visibility/submission continues to resolve from the mapped Admission Cycle and Program Offering. Candidate Profile Photo (`CANDIDATE_PROFILE_PHOTO`) renders visually in Final Preview when selected.


## ADR 167 — DatePicker dynamic year range sync
- Shared project DatePicker now clamps its visible month/year to dynamic min/max boundaries.
- Fixes blank Year selector when Academic Period Curriculum selection moves the permitted range away from the current browser year.
- Applies automatically to Academic Period and Fee Due Date bounded pickers.
- QA pending.

## ADR 168 — DatePicker Year Trigger Rendering
- Shared DatePicker now displays the authoritative `view.getFullYear()` directly in the Year trigger.
- Year selection remains interactive and uses the same bounded year option list.
- No business or persistence change.

### ADR 170 — Fee payable-date grouping contract
Fee Demand now exposes canonical `due_groups` through `FeeDueGroupingService`. Same-date liabilities are grouped only for collection/display; Fee Demand Items and Installment Schedules remain atomic. Mandatory and non-mandatory subtotals are separate. When ACTIVE installments exist, installment due dates supersede the parent item due date for that principal. This is the required input contract for Payment Collection + Allocation.

## ADR 171 — Payment Collection + Allocation (2026-09-10)
Implemented and ready for QA.
- New Payment Collection page under College Fee Management.
- Same-date ADR 170 Due Groups are collection presentation only; accounting stays item/installment/fine-wise.
- Deterministic allocation: oldest due first; Mandatory Principal → Mandatory Late Fine → Optional Principal → Optional Late Fine.
- Optional charges require explicit opt-in.
- Partial payment allowed; overpayment blocked.
- Fee Demand principal paid/outstanding/status are updated without mutating Gross Demand.
- Installment allocations update `fee_installment_schedules.paid_amount`.
- Late Fine payment is separate and unpaid fine is reflected in Fee Demand payable calculations.
- Paid Late Fine revisions are protected from silent calculator mutation.
- New payment permissions and Test Data Cleanup coverage included.
- Next gate: Payment Collection + Allocation QA. Do not start Online Payment Gateways until PASS.

### ADR 172 — Payment Collection layout consistency
Payment Collection now follows the canonical ERP page composition and no longer nests `AppLayout`; only one application header/shell renders. ADR 171 business behavior is unchanged.

## ADR 173 — Payment Due-Now Default + Student-Grouped Register — 2026-09-10
Status: IMPLEMENTED / OWNER_QA_REQUIRED.
Payment Collection preserves partial-payment flexibility but defaults to liabilities due on/before Payment Date. Future dues require explicit opt-in and are backend-enforced. Open Payables is now a student/admission-grouped, server-paginated register with expandable Fee Demands, preventing duplicate student rows as disciplines/degrees/periods scale. No migration.

### ADR 174 — Payment Upcoming Due Visibility
Implemented, QA pending. Payment Collection now exposes the earliest future unpaid principal due at both grouped-student and demand level. Future dues remain excluded from Due Now by default and may be explicitly collected in advance.

## 2026-09-10 — ADR 175 Payment allocation QA hotfix — IMPLEMENTED, QA PENDING
- Mixed mandatory + optional + future payment QA exposed category inversion: earlier-dated optional principal could be allocated before selected mandatory principal because candidate sorting was date-first.
- Allocation is now category-first globally: Mandatory Principal → Mandatory Late Fine → Optional Principal → Optional Late Fine; within each category, oldest due date then stable item order applies.
- Collect Payment now resets amount/options/reference/notes/errors from current server state every time the dialog opens, preventing stale pre-payment amounts after an Inertia refresh.
- Re-test the exact ₹20,000 mandatory + ₹2,000 optional, ₹21,000 payment case before Payment Collection sign-off.

## ADR 176 — Late Fine Rule Lifecycle + Unused Rule Cleanup — IMPLEMENTED, QA PENDING
- Late Fine Rules now use compact Edit / Power / Trash row actions.
- ACTIVE rules must be deactivated before Edit/Delete.
- Only unused INACTIVE rules with zero Late Fine Charge history can be deleted.
- Rules with calculation history remain protected/auditable; test charges must be cleaned first.
- Rule deletion is audited as `FEE_LATE_FINE_RULE_DELETED`.
- Late Fine Register date rendering no longer exposes raw ISO timestamps.
- No schema or RBAC change.

## Payment Collection QA update — 2026-09-10
- Mandatory-before-optional allocation regression PASS after ADR 175.
- Remaining optional ₹1,000 was collected; the tested Rahul demand reached zero across Due Now / Future Principal / Optional / Late Fine.
- Runtime RBAC PASS: view-only can view but not collect; collector can open collection; no-permission hides access and direct route is unauthorized.
- Payment cleanup/reversal QA remains pending before Payment Collection is formally closed.

## Payment Gateway Foundation — ADR 177 — 2026-09-10
- Gateway-independent College configuration implemented for Razorpay / Cashfree / PayU.
- Fee Head gateway product/settlement mapping implemented.
- Secrets encrypted at rest through Laravel encrypted model casts and omitted from UI payloads.
- Gateway configs start INACTIVE and require Key ID + Secret for activation.
- Status: OWNER_QA_REQUIRED.

## 2026-09-10 — ADR 178 Gateway Mapping UX + Global Toast Reliability
- ADR 177 owner QA confirmed Payment Gateway Configuration page renders in the canonical single app shell.
- Razorpay TEST / INACTIVE configuration creation passed QA.
- Individual Tuition Fee Product Code / Settlement Code mapping save and refresh persistence passed QA.
- Owner requested bulk mapping convenience; Payment Gateway mapping now supports Save All plus existing row-level Save.
- Shared toast feedback is now event-based so repeated identical server mutation messages display every time across the ERP and future standard pages.
- Status: OWNER_QA_REQUIRED for Save All and repeated-toast regression before continuing ADR 177 shared-product-code/lifecycle tests.

### ADR 179 — Payment Gateway validation UX
Implemented corrective handling for Payment Gateway business-rule failures. Missing credentials during activation and attempts to edit ACTIVE gateway credentials now return to the same Inertia page with an error toast instead of rendering the Laravel 422 exception/debug page. QA pending: retry activation with blank credentials and confirm error toast + INACTIVE status.

### ADR 180 — Multiple Gateway Credential Profiles — IMPLEMENTED / OWNER QA REQUIRED
Payment Gateway configuration now treats each `college_payment_gateways` row as a credential profile. A College can configure multiple rows for the same provider/environment, each with an independent Key ID/Secret, matching institutional Razorpay-style setups where one account/login can issue different credential pairs for different collection purposes. Fee Heads route to an exact profile through the existing mapping table. Within the same provider/environment a Fee Head can have only one ACTIVE profile mapping; saving a new ACTIVE mapping deactivates the competing one to keep future checkout routing deterministic. Product Code and Settlement Code mapping remain supported and shared Product Codes remain allowed. No Fee Payment/Allocation accounting behavior is changed.

### ADR 181 — Payment Gateway credential-profile migration compatibility
- ADR 180 migration now preserves MySQL foreign-key index requirements while removing the old one-provider-per-college uniqueness rule.
- Migration retry is safe at the index-operation level after the observed MySQL 1553 failure.

## ADR 182 — Fee Head Independent Gateway Credential Routing (2026-09-10)
Implemented. Payment gateway credential profiles remain provider credential containers, while Fee Head routing is now configured centrally and independently per Fee Head for TEST/LIVE. Different Fee Heads can point to different credential profiles; `Not assigned` is valid. Owner QA required before actual Razorpay checkout implementation.

### ADR 183 — Provider-aware gateway foundation
Implemented. Payment Gateway setup models provider-specific credential contracts for Razorpay, Cashfree and PayU. NTT DATA/Atom support has now been retired from the active registry/UI by ADR 187. Provider TEST execution must still pass independently before any provider is treated as verified.

## ADR 184 — Razorpay TEST execution — QA PASS
Razorpay TEST execution is verified with real TEST credentials. ₹1 TEST order creation, `online_payment_transactions` persistence, matching Razorpay Dashboard order, invalid-credential handling, credential restore/retest and LIVE-profile test-action suppression all passed owner QA. This still does not collect or post a student fee payment. Accounting remains under `FeePaymentService` only.

### ADR186 — Cleanup UI restoration
Test Data Cleanup uses the canonical current UI and all established cleanup menus are preserved. `Gateway Test Orders` is available as an additional transactional cleanup type for ADR184 Razorpay credential-test orders. Gateway configuration is preserved.


## ADR 187 — Cashfree + PayU TEST execution parity / webhook foundation — IMPLEMENTED, OWNER QA REQUIRED
- Cashfree: ACTIVE TEST profiles can create a real ₹1 Sandbox order through the Cashfree Create Order API and persist the provider boundary in `online_payment_transactions`.
- PayU: ACTIVE TEST profiles can run an authenticated `verify_payment` API probe using Merchant Key/Salt and persist the probe as `CREDENTIAL_TEST_API`; this proves credential/hash/API connectivity without creating a fake customer payment.
- TEST flask actions exist only on ACTIVE TEST profiles for Razorpay/Cashfree/PayU; LIVE profiles remain protected.
- Cashfree webhook endpoint verifies the raw-body HMAC signature using timestamp + Secret Key and reconciles the matching online transaction.
- PayU webhook/callback endpoint verifies reverse SHA-512 hash using Salt before reconciling the matching transaction.
- Webhooks currently reconcile provider transaction state only. They must not create Fee Payments; verified student-payment handoff remains reserved for the fee-linked checkout ADR and existing `FeePaymentService`.
- NTT DATA/Atom is removed from the active provider registry/UI. Legacy DB rows, if any, are intentionally not destructively deleted.
- Gateway Test Orders cleanup includes Cashfree/Razorpay TEST orders and PayU TEST API probes.
- No migration required.

## 2026-09-11 — ADR 187 Cashfree Sandbox QA PASS / PayU + Webhook QA pending
- Cashfree TEST credential profile activated with real Sandbox App ID / Secret Key.
- ₹1 Cashfree Sandbox order created successfully from the ERP credential-test flask action.
- Provider order persisted in `online_payment_transactions` and the same order is visible in the Cashfree Sandbox dashboard.
- Cashfree positive-path connectivity/order-creation QA is PASS.
- Cashfree webhook signature/reconciliation code is implemented but webhook delivery QA is intentionally pending until a public HTTPS callback URL/tunnel is available.
- Razorpay webhook QA is also pending for the same localhost/public-callback reason; Razorpay TEST order QA remains PASS.
- PayU TEST credential/API probe QA is the next provider test.
- Do not treat webhook code as production-verified until signature, valid event, invalid-signature and duplicate/idempotency behavior are tested against a public HTTPS endpoint.

## 2026-09-11 — Provider TEST connectivity closure
- Razorpay TEST connectivity/order QA: PASS.
- Cashfree Sandbox connectivity/order QA: PASS.
- PayU TEST authenticated API probe QA: PASS.
- Razorpay/Cashfree/PayU runtime webhook delivery QA: PENDING until a public HTTPS endpoint/tunnel is available.

## ADR 188 — Fee-Linked TEST Checkout and Verified Posting — IMPLEMENTED / OWNER QA REQUIRED
Online fee collection is now connected to the existing Payment Collection & Allocation domain without creating a parallel accounting engine.

### Current behavior
- Payment Collection exposes `Pay Online (TEST)` alongside the unchanged offline posting action.
- The selected amount and include-optional/include-late-fine/include-future flags are previewed through FeePaymentService allocation rules first.
- Every Fee Head touched by that amount must have an ACTIVE TEST routing assignment.
- All touched Fee Heads must resolve to the same credential profile for one provider checkout; otherwise the ERP blocks checkout and requires the collection to be split.
- Provider checkout is TEST-only and LIVE remains fail-closed.
- Razorpay, Cashfree and PayU each perform server-side success verification before ERP accounting is posted.
- Verified success calls the existing FeePaymentService, creates the normal Fee Payment receipt and normal deterministic allocations, and links that receipt back to the online transaction.
- Duplicate/replayed verification is idempotent through `online_payment_transactions.fee_payment_id`.
- A provider-verified payment that cannot be posted because the underlying ERP payable changed is retained as `PAYMENT_VERIFIED_UNPOSTED` for reconciliation rather than being silently lost.

### Online transaction links
`online_payment_transactions` now supports `fee_demand_id`, `admission_id`, `fee_payment_id`, `verified_at` and `posted_at`. Existing credential-test rows remain valid with these fields null.

### Webhook status
Razorpay, Cashfree and PayU webhook handlers now share the verified-posting boundary for FEE_PAYMENT transactions. Runtime delivery/signature/duplicate QA is still pending and LIVE must not be enabled until it passes.

## ADR 189 — Student Fee Ledger — IMPLEMENTED / OWNER QA REQUIRED
The next Fees Phase milestone after Payment Collection/online-payment foundation is implemented as a read-only student financial projection.

### Current behavior
- New College route/page: `/college/{college}/fee-ledger`.
- New College-delegable permission: `college_fee_ledger.view`.
- Student register is session-filtered, searchable and server-paginated.
- Ledger projects Fee Demand Items and ACTIVE Late Fine as debits; Fee Demand cancellation is preserved as a reversing credit.
- APPROVED Benefit Item sanctioned amounts and POSTED Payment Allocations are credits; later cancellation of an approved Benefit is preserved as a reversing debit.
- Running balance is computed from those source transactions; there is no new ledger/balance table.
- Verified online payments appear only through the normal posted Fee Payment allocations created by ADR 188, preventing gateway/order double counting.
- Payment Collection includes a permission-aware direct `Ledger` action for each student.
- Fee Management sidebar includes `Student Fee Ledger`.

### Next Fees Phase implementation after owner QA
Generic Adjustment / Reversal / Refund, while ADR 188 webhook/public-HTTPS QA remains a separate prerequisite before LIVE online checkout.

## ADR 190 — Generic Adjustment / Payment Reversal / Refund — IMPLEMENTED / OWNER QA PASS / CLOSED — 2026-09-15
The frozen Fee Phase correction workflow is implemented under Fee Management → Adjustments / Refunds. Manual CREDIT/DEBIT adjustments are item-scoped and auditable; complete receipt reversal restores original allocations without deleting history; partial/full refunds allocate only against originally refundable paid Fee Demand Item snapshots. Student Fee Ledger now emits the corresponding adjustment/reversal/refund history. New persistence: `fee_adjustments`, `fee_payment_refunds`, `fee_payment_refund_allocations`. New RBAC: `college_fee_adjustment.view/post/reverse`, `college_fee_refund.post`. Test Data Cleanup exposes Adjustment and Refund records independently and requires Refund cleanup before deleting a linked Payment. Fee Clearance remains NOT IMPLEMENTED and is the next Fee Phase item after owner QA PASS.

### ADR 190 Owner QA patch — Student Fee Ledger transaction badge — 2026-09-14
- QA 1 Manual CREDIT Adjustment accounting verified: ₹100 CREDIT was POSTED, `Approved Adjustments` became ₹100 and Outstanding became ₹19,900 from ₹20,000.
- QA found a presentation defect: the ledger row was incorrectly badged `Payment` although backend `FeeLedgerService` emitted `type = ADJUSTMENT`, `label = Credit Adjustment`.
- Patched `resources/js/pages/college-fee-ledger/index.tsx` to recognize `ADJUSTMENT` and `REFUND`, render specific Adjustment/Reversal labels, and remove the unsafe `Payment` fallback for ADR 190 transaction classes.
- Database/schema, RBAC, routes and accounting calculations are unchanged.
- Status: IMPLEMENTED / OWNER RE-TEST REQUIRED. QA 1 is not closed until the ledger shows `Credit Adjustment` for the existing test row.


### ADR 190 Owner QA progress — 2026-09-14
- QA 1 Manual CREDIT Adjustment: PASS after re-test. ₹100 CREDIT reduced Outstanding ₹20,000 → ₹19,900 and the Student Fee Ledger now renders `Credit Adjustment` correctly.
- QA 2 Adjustment Reversal: authoritative finance behavior PASS so far. Reversal reset Approved Adjustments to ₹0 and restored Outstanding to ₹20,000.
- QA 2 exposed a transaction-date integrity defect in the test path: the source adjustment could be future-dated (observed 2026-09-16) and then reversed at the real server date (2026-09-14), producing a chronologically valid-but-business-invalid temporary ledger balance of ₹20,100 before the future source transaction.
- Patch implemented: Manual Adjustment now rejects future dates in both UI (`DatePicker max = local today`) and backend (`before_or_equal:today`). Existing bad QA rows are intentionally not rewritten.
- QA 2 remains OWNER RE-TEST REQUIRED using cleaned/recreated non-future-dated test data. ADR 190 overall status remains IMPLEMENTED / OWNER QA REQUIRED; Fee Clearance must not start yet.

## ADR 191 — Theme-Native Application Dialog Standard — IMPLEMENTED — 2026-09-14
- Added global reusable `AppDialogProvider` and `useAppDialog()` for themed Prompt / Confirm / Alert interactions.
- Mounted the provider in `resources/js/app.tsx` so all React business pages can consume the same modal system.
- Replaced the currently detected browser-native `window.prompt`, `window.confirm`, `window.alert`, bare `confirm()` and bare `alert()` business-page interactions with ERP-themed dialogs.
- The current Fee Adjustment reversal reason now opens in the ERP theme instead of the browser/localhost prompt box.
- Existing routes, RBAC, accounting, persistence and domain behavior are unchanged; this is a cross-project UI/UX standardization.
- Standing rule: future ERP frontend work must not introduce browser-native JS dialogs. Use the reusable application dialog layer or the existing toast/flash system as appropriate.

## ADR 192 — Semantic Action Icon Standard — ACCEPTED / CURRENT QA SURFACE IMPLEMENTED — 2026-09-14
The ERP now treats meaningful action icons as a mandatory UI consistency rule for all new pages and materially modified workflows. Lucide React remains the single icon family. Important actions should normally use icon + visible text, and financially sensitive Refund/Reversal actions must always do so. The Fee Adjustments / Reversal / Refund page has been aligned: Search, Post Adjustment, receipt expand/collapse, Refund, Adjustment Reversal and Payment Reversal now use semantic icons. The shared application-dialog provider supports an optional confirm-action icon so reversal prompts/confirmations preserve the same interaction meaning. Legacy pages are migrated when materially touched or through an explicitly approved consistency pass rather than through an unsafe blanket rewrite.

### ADR 190 Owner QA — same-day ledger chronology — 2026-09-14
- Manual DEBIT Adjustment authoritative Fee Demand accounting PASS (`20,000 + 100 = 20,100`).
- Same-day ledger chronology defect found during verification was fixed in `FeeLedgerService` by using posting/reversal timestamps as tie-breakers while retaining the business date as the displayed date.

### ADR 190 Owner QA — installment paid-floor reconciliation — 2026-09-14
- QA 5 found a real installment reconciliation defect after cumulative CREDIT adjustments on a partially paid installment schedule.
- Authoritative Fee Demand accounting remained correct: ₹22,000 gross − ₹12,000 approved CREDIT adjustments − ₹5,000 posted payment = ₹5,000 outstanding.
- The old proportional rebalance floored the first installment at its ₹5,000 paid amount but failed to redistribute the resulting ₹1,000 excess away from the later installment, causing Due Groups to expose ₹6,000 while the demand correctly showed ₹5,000.
- `FeeInstallmentAdjustmentService` now enforces both invariants: active installment amounts total the exact adjusted Fee Head liability, and no installment amount can fall below already-paid principal.
- New schedules store stable allocation percentages; `FeeDueGroupingService` also defensively caps installment collection/display to the authoritative Fee Head open amount.
- Status: code fix complete; **QA 5 OWNER RE-TEST REQUIRED**. ADR 190 remains `IMPLEMENTED / OWNER QA REQUIRED`.

### ADR 190 / ADR 193 Owner QA — stable installment basis after adjustment reversal — 2026-09-14
- QA 5 confirmed authoritative liability totals but exposed allocation drift when reversing a prior CREDIT on a legacy schedule whose `allocation_percentage` was NULL.
- The rebalance service now uses the original schedule allocation basis for both adjustments and reversals. New schedules already persist percentages; legacy schedules recover the original split from audit history and lazily persist it.
- Paid principal remains an immutable floor.
- Installment due dates now remain strictly a due-state concern: expired dates classify remaining balances as overdue but do not reallocate liability to later installments.
- Example under QA: original ₹10,000 + ₹10,000 schedule, ₹5,000 paid on #1, liability restored to ₹12,000 must render effective ₹6,000 + ₹6,000, hence ₹1,000 + ₹6,000 outstanding.
- Status: IMPLEMENTED / OWNER RE-TEST REQUIRED. QA 5 remains open until the reversal screen re-renders the expected ₹1,000 + ₹6,000 split and total ₹9,000 including the ₹2,000 Library Fee.


### ADR 190 Owner QA consolidated status — 2026-09-14
The current Generic Adjustment / Payment Reversal / Refund QA run has progressed as follows:

- QA 1 Manual CREDIT Adjustment — **PASS**.
- QA 2 Reverse CREDIT Adjustment — **PASS**.
- QA 3 Manual DEBIT Adjustment — **PASS**.
- QA 4 Reverse DEBIT Adjustment — **PASS**.
- QA 5 Installment adjustment, cumulative CREDIT, paid-floor protection, exact reconciliation and stable original allocation basis on reversal — **PASS after fixes**.
- QA 6 Full Payment Reversal, installment restoration and second-reversal prevention — **PASS**.
- QA 7 Partial Refund accounting / ledger — **PASS**.
- QA 8 Over-refund protection — **PASS**.
- QA 9 Full receipt reversal after a posted partial Refund — **PASS (blocked as required)**.
- Fully non-refundable receipt behavior — **PASS so far**: receipt with Refundable ₹0 exposes no Refund action.
- Fully refundable receipt behavior — **PASS so far**: partial Refund reduces remaining refundable balance correctly.

Still open before ADR 190 can be marked fully QA PASS:
1. **Mixed-allocation single receipt QA** — one receipt must contain both refundable and non-refundable Fee Head allocations; only the refundable allocation may be refunded.
2. **RBAC QA** — independently verify `college_fee_adjustment.view`, `college_fee_adjustment.post`, `college_fee_adjustment.reverse`, and `college_fee_refund.post`.
3. **College scope QA** — a College user must not view or mutate another College's demands/receipts/adjustments/refunds.
4. **Test Data Cleanup dependency QA** — re-test after ADR 194: Refund -> POSTED/REVERSED Payment -> Installment Schedule -> Fee Demand, including the exact reversed-payment case that previously became unreachable.
5. **Final reconciliation spot-check** — confirm Fee Demand outstanding, Fee Collection Due Groups and Student Fee Ledger closing balance agree after the remaining mixed-refund/cleanup tests.

Fee Clearance remains **NOT IMPLEMENTED** and must not start until the ADR 190 owner-QA gate is closed.

### ADR 194 — Reversed Fee Payment cleanup visibility — IMPLEMENTED / OWNER RE-TEST REQUIRED — 2026-09-14
Owner QA found that a REVERSED Fee Payment retained its audit allocations (correctly) but Test Data Cleanup listed only POSTED payments. The related Installment Schedule therefore remained blocked by a payment allocation that the cleanup UI could not reach. Test Data Cleanup now lists and cleans both POSTED and REVERSED Fee Payments. REVERSED payment cleanup does not decrement installment `paid_amount` a second time because controlled reversal already restored it. Refund-first dependency protection remains unchanged.

### QA follow-up — TEST online fee transaction cleanup (2026-09-14)
Implemented fix for a Test Data Cleanup edge case where a Fee Demand remained referenced by an unposted TEST online fee transaction and deletion surfaced a database FK error. The cleanup service now pre-detects the dependency, exposes the unposted TEST fee transaction under Gateway Test Orders, and requires it to be cleaned before the Fee Demand. **Owner QA required for this edge case.**

ADR 190 overall QA still has these open areas after this fix: mixed refundable/non-refundable single-receipt QA; RBAC permission QA; College scope QA; this TEST-online-transaction cleanup re-test; and final cross-screen reconciliation spot-check.



## ADR 190 / 196 — Finance QA status update (2026-09-15)
- **PASS:** mixed refundable/non-refundable single-receipt allocation. QA receipt ₹22,000 correctly exposed only ₹2,000 refundable (₹20,000 non-refundable Tuition + ₹2,000 refundable Library).
- **PASS:** first ₹500 mixed-receipt refund restored only Library Fee liability; Fee Demand and Due Groups showed ₹500 outstanding and did not reopen Tuition.
- **PASS:** ADR 194 reversed-payment Test Data Cleanup dependency re-test; owner confirmed the cleanup path now works.
- **DEFECT FIXED / OWNER RE-TEST REQUIRED:** second/subsequent refund previously crashed with `Undefined property: stdClass::$amount`; ADR 196 makes the original refundable allocation amount explicit and preserves cumulative refund subtraction.
- **AUDIT QA REQUIRED:** verify that the second refund performed by another authorized staff user in the same College is recorded in the existing College-scoped Audit Log with the correct actor and finance context.
- **REMAINING ADR 190 GATES:** ADR 196 second-refund re-test; delegated-staff audit verification; four finance RBAC permissions; cross-College isolation; ADR 195 online-payment cleanup re-test if not separately confirmed; final Fee Demand / Due Groups / Student Fee Ledger reconciliation.
- Fee Clearance remains not implemented / blocked behind completion of this QA gate.


### ADR 190 / 196 owner re-test confirmation — 2026-09-15
- **PASS:** second/subsequent partial refund after ADR 196 hotfix. Owner confirmed the repeat-refund path now works correctly and no longer raises `Undefined property: stdClass::$amount`.
- **PASS:** finance RBAC with another staff user of the same College. Owner confirmed delegated-staff access/action behavior is working for the ADR 190 QA surface. This closes the ADR 190 RBAC QA gate for `college_fee_adjustment.view`, `college_fee_adjustment.post`, `college_fee_adjustment.reverse`, and `college_fee_refund.post`.
- **PASS:** Test Data Cleanup owner re-test. The previously blocked reversed-payment cleanup path remains confirmed working; owner also confirms the current test-clean flow passes.
- **Still open:** delegated-staff College Audit Log evidence/verification unless separately confirmed; cross-College isolation; ADR 195 unposted TEST online-payment cleanup edge if not separately confirmed; final Fee Demand / Due Groups / Student Fee Ledger reconciliation spot-check.
- Fee Clearance remains blocked until the remaining ADR 190 owner-QA gates are closed.


## ADR 190 FINAL OWNER QA CLOSURE — 2026-09-15
**Status: IMPLEMENTED / OWNER QA PASS / CLOSED.**

The owner has now confirmed the remaining ADR 190 gates in the live QA flow. The earlier `OWNER QA REQUIRED`, `OWNER RE-TEST REQUIRED`, `AUDIT QA REQUIRED`, and `Still open` statements above are retained only as historical progress notes and are superseded by this closure record.

Final confirmed gates:
- Manual CREDIT/DEBIT adjustment posting and reversal — **PASS**.
- Installment paid-floor, exact liability reconciliation and stable original allocation basis — **PASS**.
- Full Payment Reversal and second-reversal prevention — **PASS**.
- Partial/full Refund, over-refund protection and reversal-after-refund blocking — **PASS**.
- Fully refundable, fully non-refundable and mixed refundable/non-refundable single-receipt behavior — **PASS**.
- Second/subsequent cumulative refund after ADR 196 — **PASS**.
- ADR 190 College finance RBAC for delegated staff — **PASS**.
- Delegated-staff College Audit Log recording / actor and College-scope visibility — **PASS**.
- College-scope isolation / cross-College denial — **PASS**.
- Test Data Cleanup, including ADR 194 reversed-payment dependency and ADR 195 unposted TEST online-payment dependency path — **PASS**.
- Final Fee Demand / Fee Collection Due Groups / Student Fee Ledger reconciliation spot-check — **PASS**.

**Phase consequence:** the ADR 190 correction/refund/reversal QA gate is closed. Fee Clearance is no longer blocked by ADR 190 and is the next Fee Phase implementation. This closure does not mark Fee Clearance itself implemented.

## 2026-09-16 — Fee Clearance (ADR 197)
**Status: IMPLEMENTED / OWNER QA REQUIRED**

Fee Management now includes a College-scoped Fee Clearance register/detail page. Clearance is an authoritative read-only projection for CONFIRMED Admissions: only active/non-cancelled Fee Demand Items whose snapshot is `is_enrollment_clearance_required = true` participate. Current item liability is derived from the Student Fee Ledger financial history rather than a duplicate stored clearance balance. States are PENDING, CLEARED and NOT_REQUIRED; CLEARED and NOT_REQUIRED expose an open Enrollment gate. Refunds, reversals and debit adjustments automatically re-open the gate when they restore required-item liability. Permission: `college_fee_clearance.view`. No Student Enrollment behavior is implemented yet.

**Next gate:** owner QA must pass ADR 197 before Student Enrollment / Student Lifecycle implementation begins.

## 2026-09-16 — Fee Clearance premium UI / scalable filtering refinement
Fee Clearance now follows the frozen UI standards: shared themed Select controls, Lucide semantic icons, visible Inertia loading state with duplicate-action protection, and server-side Academic Session → Programme Offering dependent filtering. Programme Offering is scoped by College + selected Session and preserved through search/pagination/detail requests. No database migration or accounting-rule change is introduced by this refinement. Owner QA remains required.

## 2026-09-16 — ADR 198 Shared Application Loading Infrastructure — IMPLEMENTED / OWNER QA REQUIRED
- Added application-level `AppLoadingProvider` around the Inertia app, parallel to the existing shared Toast and App Dialog infrastructure.
- Standard Inertia navigation/filter/pagination/reload requests now receive automatic theme-native loading feedback project-wide.
- Added `useAppLoading()` for pages that need contextual table/card/filter blocking while sharing the same authoritative Inertia request state.
- Fee Clearance migrated from page-local router start/finish callbacks to the shared loading infrastructure; its existing table overlay and duplicate-action protection now consume the global state.
- Form/mutation buttons continue to use Inertia `processing`; non-Inertia async operations remain responsible for shared contextual Spinner/Skeleton/progress states.
- No database change.


### 2026-09-16 — Shared loading UI refinement (ADR 198)
- Global Inertia loading is now visibly centered in the work area with the canonical Spinner and a restrained blocking backdrop.
- Existing Inertia pages receive the generic loading state automatically; materially touched pages can provide contextual resource labels without registering local router lifecycle handlers.
- Student Fee Ledger and Fee Clearance use contextual shared loading labels.
- Database impact: NONE.

### 2026-09-16 — Fee Management navigation refinement
College Fee Management sidebar presentation is workflow ordered: Fee Setup → Scholarship / Benefits → Payment Gateways → Fee Demands → Student Benefits → Payment Collection → Student Fee Ledger → Adjustments / Refunds → Late Fine / Penalty → Fee Clearance. Each entry uses a semantic theme-native Lucide icon. Fee Clearance remains the final derived financial gate before downstream Enrollment. This is navigation/UI only; no database or authorization behavior changed.

## 2026-09-16 — Student Enrollment ENR-0 / ADR 199
Status: **IMPLEMENTED / OWNER QA REQUIRED**.

- Fee Clearance is Owner-QA accepted and the Student Enrollment branch is now eligible.
- Added the stable `students` master, `student_enrollments` academic-membership table and `student_profile_values` dynamic-profile snapshot table.
- Completed `applicant_profiles.student_id -> students.id` FK promised by ADR 034.
- Added Admission Form field mapping metadata (`APPLICATION_ONLY|STUDENT_PROFILE` + optional profile key); no existing dynamic field is auto-promoted.
- Student identity remains separate from session/programme placement. ENR-3 owns UID/roll-number generation; ENR-4 owns CSV import UI/workflow.
- No Student Enrollment page/action is implemented in ENR-0.
- Next eligible milestone after Owner QA PASS: **ENR-1 Enrollment Eligibility Queue**.

## 2026-09-16 — Fee Clearance owner QA closure
Owner confirmed Fee Clearance end-to-end behavior: required liability PENDING -> CLEARED after settlement; receipt reversal and eligible refund re-open required liability and return clearance to PENDING/BLOCKED; non-clearance-required unpaid heads do not block enrollment. Programme Offering filtering, shared loading behavior and Fee Management navigation refinements were also accepted. ADR 197 is **OWNER QA PASS / CLOSED**.

## 2026-09-16 — ENR-0 Owner QA closure / ENR-1 Enrollment Eligibility Queue
**ENR-0 / ADR 199: OWNER QA PASS / CLOSED.** Owner verified migration execution/status, all three foundation tables, nullable `applicant_profiles.student_id` FK, Application Form Builder regression, historical Application regression, and existing Admission regression. ENR-0 is closed.

**ENR-1: IMPLEMENTED / OWNER QA REQUIRED.** Added College-scoped Student Enrollment eligibility queue at `/college/{college}/student-enrollments`. It lists only CONFIRMED Admissions in the selected Academic Session / Programme Offering, consumes `FeeClearanceService` as the sole financial gate, and derives `READY`, `BLOCKED`, or `ENROLLED` without creating/updating Student or Enrollment records. Permission: `college_student_enrollment.view`. ENR-2 remains blocked until ENR-1 Owner QA PASS.

## Student Enrollment checkpoint — 2026-09-16 / ENR-2
- ENR-0 / ADR 199: IMPLEMENTED / OWNER QA PASS / CLOSED.
- ENR-1 / ADR 200: IMPLEMENTED / OWNER QA PASS / CLOSED.
- ENR-2 / ADR 201: IMPLEMENTED / OWNER QA REQUIRED.
- READY rows can now be enrolled through the canonical transaction; server rechecks Fee Clearance and College/Admission scope before mutation.
- ENR-3 Student Identity is BLOCKED until ENR-2 Owner QA PASS.

### ENR-2 QA refinement — 2026-09-16
Enrollment confirmation now displays Discipline using the existing Application Academic Preference relationship. This is a presentation/read-model refinement only; no database change.

## ENR-2 corrective completion — ADR 202 (2026-09-16)
- Dynamic Application Form fields now expose **Data Usage** in University and College-owned field Add/Edit dialogs.
- Existing/historical fields remain `APPLICATION_ONLY` unless explicitly changed. For newly created dynamic fields, the Form Builder defaults Data Usage to `STUDENT_PROFILE`; administrators may choose `APPLICATION_ONLY` for admission-specific answers.
- Policy changes are prospective for Student creation: existing `student_profile_values` are not backfilled or rewritten.
- No database structural change/migration in ADR 202; it exposes the ENR-0 columns already present on `college_admission_form_fields`.
- ENR2-11 QA is pending before ENR-2 closure.

### ENR-3 Student Identity — 2026-09-17
IMPLEMENTED / OWNER QA REQUIRED (ADR 203). Student UID and University Roll are Student-owned; Class Roll is Enrollment-owned; Exam Roll remains Examination-owned. College-specific formats and lock-protected sequences are implemented. Existing ENR-2 enrollments can be assigned from Student Identity; future enrollment assignment uses the same transactional service. ENR-4 remains blocked until ENR-3 Owner QA PASS.

### ENR-3 QA cleanup support — 2026-09-17
ENR-3 is still IMPLEMENTED / OWNER QA REQUIRED. The existing Full Academic Test Reset is ENR-3-aware and removes identity settings/sequences only after affected Student lifecycle test rows are removed. Targeted Student identity cleanup preserves sequence advancement and never reuses consumed numbers. See `.project/AI/QA/ENR3_TEST_DATA_CLEANUP.md`.

- ENR-3.3 implemented: Class Roll sequence scope is configurable per College as Programme Offering (default) or Discipline; existing Class Rolls remain immutable.

### ENR-3.4 correction (2026-09-17)
- Student Enrollment no longer auto-assigns institutional identity.
- Student Identity Assign is the authoritative/manual issuance action.
- Test Data Cleanup includes identity-only cleanup preserving Student + Enrollment and never rewinding sequences.
- QA pending owner verification.

## ENR-3 closure / ENR-4 implementation — 2026-09-17
- **ENR-3 / ADR 203: OWNER QA PASS / CLOSED.** Owner verified manual identity assignment, configurable Class Roll scope, Discipline UI, safe test sequence-reset semantics, Student Management RBAC View/Manage alignment and Identity audit events.
- **ENR-4 / ADR 204: IMPLEMENTED / OWNER QA REQUIRED.** Student Management now includes College-scoped CSV Student Import / Migration: template → upload → Session/Programme Offering → mapping → validation/preview → confirmed transactional import.
- IMPORT provenance writes the same `students` and `student_enrollments` architecture; no fake Admission/Application/Fee records are created. Blank identity remains Pending.
- ENR-4 adds nullable `student_enrollments.discipline_id` so imported students can participate in Discipline filtering and Discipline-scoped Class Roll identity rules. No new domain table.

### ENR-4.2 Student Import dynamic profile mapping — 2026-09-17
IMPLEMENTED / OWNER QA PENDING. Student Import now derives offering-applicable Admission Form fields marked STUDENT_PROFILE, supports searchable one-to-one CSV column mapping, and persists imported values to student_profile_values. No schema change.

## ENR-4.3 — current QA state (2026-09-17)
Drag-and-plug mapping and Programme Offering-scoped saved mapping/template infrastructure are implemented. Migration `2026_09_17_140000_create_student_import_mappings.php` is pending Owner environment execution/QA. ENR-4 remains OWNER QA IN PROGRESS; do not mark ADR 204 closed until save/load/template and final import regression pass.

### 2026-09-17 — ENR-4.4 Curriculum-linked import (ADR 204)
Student Import academic context now follows the same Programme Offering → Curriculum resolution used by Admission Forms. Discipline, Specialization and curriculum Choice categories are validated through `ApplicantAcademicPreferenceService`; mandatory courses resolve automatically. Enrollment now carries Curriculum/Discipline/Specialization and resolved enrollment-course links for both Admission and Import routes. Mapping UI uses accordions with Core Student open by default. OWNER QA PENDING.

### ENR-4.4.1 — Migration Recovery + DB Documentation Hardening — OWNER QA PENDING
- Corrects MySQL error 1059 from an overlong generated FK name in ENR-4.4.
- Uses explicit short FK names and restart-safe existence checks for known partial DDL.
- DB impact documentation now records schema ownership, relationships, constraints/indexes and recovery behavior.
- ADR 205 makes DB documentation completeness a permanent milestone closure gate.

## ENR-4.5 — Student Import validation contract (2026-09-17)
Status: IMPLEMENTED / OWNER QA PENDING.
Student Import now enforces required mapping and row-level required values for applicable Admission Form `STUDENT_PROFILE` fields, while `APPLICATION_ONLY` remains excluded. Curriculum academic targets remain authoritative through `ApplicantAcademicPreferenceService`; invalid/missing configured academic values block import and never create masters. If no applicable ACTIVE Admission Form Template exists, the UI explicitly states that import will use Core Student + Curriculum Academic Context only and permits the migration. No DB schema change; DB impact documentation updated.

### ENR-4.6 — Academic Choice Parity — 2026-09-17
- Student Import now mirrors Admission academic package vs genuine course-choice semantics.
- Course-choice targets are dynamic from curriculum slot min/max; 2 choices => two sockets, 3 => three sockets, etc.
- Genuine choices accept actual Curriculum Course Codes; Offered From/Common/category/term context resolves internally from Curriculum Course Mapping.
- Package mode remains one configured academic-option selection with linked papers resolved internally.
- No schema migration. Owner QA remains in progress; ENR-4 is not closed.

### ENR-4.8 — Offered-From Admission/Import parity — 2026-09-17
- Owner corrected the user-facing choice contract: non-package academic choices now show/accept Offered From (History, Hindi, Common / Interdisciplinary, etc.), not internal course codes.
- Admission Form stores the resolved Curriculum Course Mapping ID behind the Offered From selection; final review also presents Offered From rather than exposing course codes.
- Student Import dynamically creates Offered From choice sockets from Curriculum slot min/max and resolves CSV Offered From code/name through the same `ApplicantAcademicPreferenceService` before writing Enrollment course choices.
- Ambiguous same-slot Offered From configuration (more than one active mapping for one source) is blocked rather than guessed.
- ENR-4 remains OWNER QA IN PROGRESS. Admission and Import parity must be verified against the same Curriculum before closure.

### ENR-4.9 — Candidate/Application Offered-From parity
- College `Applications / Candidate Eligibility` create/edit mirrors the public Applicant Admission Form: users choose Offered From while existing curriculum-course-mapping IDs remain the persisted academic choice.
- Academic Package behavior is preserved.
- No schema change / no migration. ENR-4 remains OWNER QA IN PROGRESS.

### ENR-5 — Canonical Enrollment Academic Normalization — 2026-09-18
- ADMISSION and IMPORT now share `StudentEnrollmentAcademicContextService` for canonical Enrollment academic persistence.
- Legacy pre-ENR-4.4 ADMISSION enrollments can be dry-run/repaired from their own authoritative Application Academic Preference + saved Application Course Choices with `students:normalize-enrollment-academics`.
- Conflicting/missing provenance is never guessed; it is reported `NEEDS_REVIEW`.
- Downstream Student Profile/Attendance/Examination/Result/Marksheet/Promotion must consume Student + Enrollment canonical context and must not branch by provenance.
- DB impact: data normalization only; no schema migration.
- Status: IMPLEMENTED / OWNER QA REQUIRED.

## ADR 207 — Test Data Cleanup reset levels — 2026-09-18
- Added Admission & Merit Workflow Reset for repeatable admission QA without rebuilding submitted applicant forms.
- Module reset preserves Applications/answers, Applicant identities, Scores, Programme Offering, Intake/Seat Capacity, Curriculum and academic masters; generated downstream Merit/Seat/Admission/Admission-Student data is reset child-first.
- Full Academic Test Reset explicitly preserves Users/login accounts including the designated `test@...` bootstrap login.
- Full reset now includes canonical Student children and IMPORT-source Students; Enrollment Course Choices are deleted before Enrollments.
- No schema change / no migration. Owner QA pending; see `.project/AI/QA/ADR207_TEST_DATA_CLEANUP_QA.md`.


### 2026-09-18 — ADR 207 follow-up: Academic Calendar Period cleanup
Test Data Cleanup now exposes Academic Calendar Period assignments under Academic Setup as targeted QA cleanup records. Deleting one removes only the session/calendar period assignment, preserves the parent calendar and academic masters, and preserves calendar events. No Fee Management behavior was changed. No schema migration. Owner QA pending.

## 2026-09-18 — ENR-5 CLOSED / ENR-6 Student Profile started
ENR-5 / ADR 206 is OWNER QA PASSED / CLOSED. Owner QA confirmed legacy Admission normalization, idempotent rerun, and a fresh Admission-route Student enrollment persisted canonical academic context without requiring normalization.

ENR-6 / ADR 208 is OWNER QA PASSED / CLOSED. Student Management now includes Student Profile with server-paginated Session → Programme Offering filtering, one-Student detail/edit, governed STUDENT_PROFILE values, read-only Student Identity fields, and read-only canonical Enrollment/course context. Admission/Import remain provenance only. Profile mutation is protected by `college_student_profile.edit` and audited as `student.profile.updated`. FILE/IMAGE profile values remain read-only in ENR-6.1. No Student-domain schema change; permission/reference migration only.

### 2026-09-18 — ENR-6.2 profile presentation/lifecycle correction
Student Profile now uses the shared DatePicker for DOB, promotes the governed Candidate Profile Photo to the profile header with permission-gated private viewing and auditable replacement, and presents current Enrollment context at the top. Academic choices are category-labelled `APPLICANT_CHOICE` summaries resolved through canonical Curriculum mappings; AUTO_MANDATORY courses and internal IDs are not exposed as profile choices. OWNER QA remains pending.

### 2026-09-19 — ENR-6.3 Import-source profile photo parity
Student Profile photo capability now follows the applicable governed Admission Form configuration for the Student's current Programme Offering rather than the existence of an Admission-copied photo value. IMPORT Students therefore receive the same empty photo slot and Add Photo action when `CANDIDATE_PROFILE_PHOTO` is configured as `STUDENT_PROFILE`. First upload creates the canonical profile-value row; subsequent replacements reuse it. CSV import remains unchanged and does not map FILE/IMAGE fields. Owner QA pending.

### 2026-09-19 — ENR-4.6C College Student account credential correction / ADR 204C
- College Users -> Students retains current-session Session -> Programme Offering -> Discipline filtering, canonical Admission/Import visibility, University-consistent Active/Inactive status, View Profile and login Enable/Disable.
- Student `Send Password Reset Link` is superseded by `Generate New Temporary Password`; the existing password is invalidated, `must_change_password=true`, and the replacement is delivered through the actor-scoped one-time credential CSV. College Staff reset-link behavior is unchanged.
- Unified Student account regeneration audits `student.account.temporary_password_regenerated`; no plaintext credential is audited and no database schema change is required.
- Student Portal future information architecture is now documented in MASTER_DEVELOPMENT_HIERARCHY without changing or renumbering the existing phase roadmap. It is a downstream consumer specification, not a new immediate implementation phase.
- ADR 204C is OWNER QA PASSED / CLOSED.


## 2026-09-20 — Phase 13 Course Delivery started / Course Offerings implemented
Owner confirmed ENR-6 / ADR 208 and ADR 204C QA PASS / CLOSED. The next hierarchy milestone is Phase 13 Course Delivery -> Course Offerings. ADR 209 implements batch-level Course Offering as `Batch + Curriculum Course Mapping`, preserving the existing Program Offering, Curriculum, Batch and Section architecture without duplication. College-scoped create and activate/deactivate flows, RBAC, audit events, and database integrity guards are implemented. New Course Offerings start INACTIVE. Section-specific delivery is intentionally deferred to later Faculty Allocation/Class Scheduling. Status: IMPLEMENTED / OWNER QA REQUIRED.

### 2026-09-20 — Phase 13 Course Offering creation refinement
Course Offering creation now derives applicable delivery records in bulk from existing University Curriculum structure using Batch + University-defined Discipline + Term. Mandatory common/discipline mappings are included automatically; choice mappings are included only where enrolled students selected them. Curriculum credits/countability are displayed read-only. No schema/hierarchy change. Owner QA remains required.


### 2026-09-20 — Phase 13 Course Offering preview layout correction
The Add Course Offerings dialog now uses a wider responsive layout and explicit Curriculum Preview column sizing so Course, Scope, Credits, Counting and Rule remain readable on desktop. Small viewports remain bounded to the viewport and the preview table can scroll horizontally. No business logic, schema, hierarchy, or Course Offering derivation rules changed. Owner QA remains required.

- 2026-09-20: Course Offerings curriculum-preview modal responsive overflow corrected: shared Dialog breakpoint width is overridden, forced table minimum width removed, and preview is horizontal-scroll-free with wrapped cells and vertical-only list scrolling.

### 2026-09-20 — Phase 13 Course Offering pre-enrollment correction
Course Offering creation no longer depends on Student Enrollment or Student Course Choice records. For Batch + University-defined Discipline + Term, all active applicable MANDATORY mappings are auto-included/locked and all active applicable CHOICE mappings are visible/selectable for College delivery planning. Credits/countability remain inherited read-only from the University Curriculum. No schema/hierarchy change. Owner QA remains required.

### 2026-09-20 — Phase 13 Course Offering delivery navigation refinement
Course Offering delivery list now follows Session -> Program Offering -> Batch -> Discipline filters, defaulting Session to the current Academic Session and Program Offering/Batch to the first applicable context. Results are grouped from existing University Curriculum metadata as Discipline -> Semester/Term -> optional Specialization -> Course Offering. Semester rows are expandable and show total COUNTABLE Curriculum credits; specialization is derived from the existing Curriculum Course Mapping and Academic Discipline hierarchy and is not duplicated on Course Offering. No schema/hierarchy change. Owner QA remains required.


### 2026-09-20 — Phase 13 Course Offering Curriculum-credit linkage correction
Course Delivery semester/discipline credit summaries no longer sum Course Offering rows. They now consume the existing University Curriculum Slot rules: MANDATORY contributes Slot credits once; CHOICE contributes Slot credits × min_selection for Required Credits and × max_selection for Maximum Credits; NON_COUNTABLE Slots are excluded from countable totals. Multiple offered alternatives in one Slot therefore do not inflate curriculum credits. Course Offering remains a delivery instance and does not own or override academic credit rules. No schema/hierarchy change. Owner QA remains required.

## 2026-09-21 — Course Offerings closure / Faculty Allocation (ADR 210)
Course Offerings / ADR 209 is OWNER QA PASSED / CLOSED. Faculty Allocation is IMPLEMENTED / OWNER QA REQUIRED. It consumes Course Offerings and active College-scoped Faculty users, supports Batch-wide or same-Batch Section scope, teaching role and optional weekly load, and includes inactive-first lifecycle, RBAC, audit and dependency-safe cleanup. Timetable remains blocked until Faculty Allocation owner QA passes.

### Faculty Allocation selection refinement
Faculty Allocation defaults to the Current Academic Session and follows searchable Session → Program Offering → Discipline → Semester/Term → Course Offering selection. Faculty is searchable by name/email/role, and the page links to College Users and College Roles for onboarding. `UI_SEARCHABLE_SELECT_STANDARD.md` is the shared project-wide rule for dynamic/large selectors.

## 2026-09-21 — Faculty Allocation closure / next three Course Delivery milestones
Faculty Allocation / ADR 210 is OWNER QA PASSED / CLOSED. Rooms, Timetable and Class Scheduling are IMPLEMENTED / OWNER QA REQUIRED under ADR 211. The authoritative chain is Course Offering → Faculty Allocation → recurring Timetable Entry → dated Class Schedule, with optional College Room linkage, overlap protection, RBAC, audit and child-first cleanup. Attendance is next and remains blocked until owner QA passes.


### 2026-09-21 — Faculty Allocation College-role eligibility permission correction
- College Role → Permissions now exposes `college_faculty_allocation.eligible` as a College-delegable eligibility marker even when the assigning College administrator does not personally hold that marker.
- Authorized College permission managers can assign/remove the marker from College-owned roles (for example, Faculty); normal College permission delegation remains constrained to permissions the actor holds.
- University role/permission behavior is intentionally unchanged by this correction.
- No schema migration. Faculty Allocation remains IMPLEMENTED / OWNER QA REQUIRED.

### 2026-09-22 — Phase 13 Class Scheduling scheduled-edit correction
Class Scheduling now permits authorized correction of a dated occurrence only while status is `SCHEDULED`. Edit supports Timetable entry, class date and note; server validation is re-run and time/Room snapshots are refreshed from the selected active Timetable. `COMPLETED` and `CANCELLED` are terminal locked states and cannot be edited or reopened. No schema migration. Rooms/Timetable/Class Scheduling remain IMPLEMENTED / OWNER QA REQUIRED under ADR 211.

## 2026-09-22 — Phase 14 Attendance Operations foundation / ADR 212
Status: **IMPLEMENTED — OWNER QA REQUIRED**.

College Attendance now consumes the exact Class Schedule and canonical Student Enrollment academic context. It resolves the applicable ACTIVE + APPROVED Academic Policy and requires its Attendance Rule, supports complete-roster draft/finalize entry, locks finalized raw records, completes the Class Schedule transactionally, permits permissioned audited correction reopening, and displays policy-rounded finalized attendance/shortage using the resolved Attendance Rule calculation scope. `COURSE` uses the exact Course Offering, `TERM` aggregates the exact Curriculum Term within the same Programme Offering, and `OVERALL` aggregates finalized attendance across the same Programme Offering. Attendance does not mutate Fee Demand or any financial history. Condonation, special exemption and final examination eligibility remain subsequent Attendance milestones.

### 2026-09-22 — Timetable inactive-edit correction
Timetable now exposes a prefilled Edit action for INACTIVE entries to actors with `college_timetable.manage`. Server-side update additionally verifies that the target Timetable entry itself belongs to the route College before applying the existing active-allocation, Room, effective-period and conflict rules. ACTIVE entries remain locked until deactivated; existing dated Class Schedule snapshots are not rewritten.

### 2026-09-22 — Canonical Student placement and delivery-scope correction
Attendance Owner QA exposed a canonical placement gap: enrolled Students could have valid identity and course-choice context while `student_enrollments.batch_id` / `section_id` remained NULL, causing section-scoped Attendance rosters to resolve empty. Student Identity is now the single operational UI for audited bulk placement into an ACTIVE Batch and ACTIVE child Section belonging to the same Programme Offering; the duplicate placement action was removed from Student Enrollment. Attendance remains strict and consumes canonical Enrollment placement plus exact course choice. Faculty Allocation now submits an explicit `BATCH` or `SECTION` delivery scope, with no implicit first-Section fallback. Section has no capacity field: strength is derived from active enrolled placements. Room capacity is physical capacity and Timetable/Class Schedule writes and Timetable activation reject rooms smaller than the derived applicable course roster. Fee Demand is unaffected because placement does not create, cancel, or recalculate financial demand. No schema migration or parallel mapping table is introduced. Status: IMPLEMENTED / OWNER QA REQUIRED.

### 2026-09-22 — Academic Calendar to Class Schedule linkage
Class Schedule create/edit now requires the ACTIVE College-adopted Academic Calendar for the Programme Offering session, an ACTIVE period containing the date for the exact course Curriculum Term, and a date outside effective ACTIVE Holiday/Vacation ranges. ACTIVE College overrides replace the University event dates. Recurring Timetable remains a rule; the dated occurrence is the calendar enforcement boundary. Attendance inherits this validity through Class Schedule. Placement and Fee Demand are unchanged. No schema migration. Status: IMPLEMENTED / OWNER QA REQUIRED.

## Shared Scheduling Time Picker Consistency — 2026-09-23
**IMPLEMENTED — OWNER QA REQUIRED**

- Course Delivery Timetable create/edit now uses the shared `TimePicker` already established by Interview Scheduling instead of browser-native `type="time"` controls.
- Start and End Time use explicit hour/minute/AM-PM selectors with a 5-minute step while preserving the existing `HH:mm` backend payload.
- `UI_UX_GUIDELINES.md` now makes shared `TimePicker` reuse the default for future time-only scheduling fields, preventing page-specific/native time-picker drift.
- No database/schema or backend validation contract change.

## 2026-09-24 — Attendance controlled exceptions and final eligibility / ADR 214

The next three Phase 14 milestones are IMPLEMENTED / OWNER QA REQUIRED: Attendance Condonation, Medical/Special Attendance Exemption, and final Examination Attendance Eligibility. Condonation is policy-limit constrained; special exemption is policy-enabled; both are separate audited decisions and never rewrite raw attendance. Final eligibility resolves Course/Term/Overall attendance, normal threshold, approved exception and the policy's examination-attendance requirement into a persisted Student Enrollment + Course Offering snapshot. Examination is the next downstream phase consumer after QA.

## 2026-09-24 — Phase 15 Internal Assessment first three milestones / ADR 215

Assessment Setup, Assignment and Quiz are IMPLEMENTED / OWNER QA REQUIRED. Assessment components are configurable per Course Offering and snapshot the resolved Academic Policy. Assignment/Quiz activities require the exact ACTIVE component and Faculty Allocation, remain inside the governed Curriculum Term calendar period, and follow DRAFT → PUBLISHED → CLOSED. Publication transactionally snapshots the exact canonical Enrollment roster for stable later Marks Entry. Mid Semester is the next hierarchy milestone after QA.

Corrective verification: all three Internal Assessment GET endpoints now use exact `{college}` implicit-binding parameter names. Direct route-binding execution for College `1` returned valid Inertia responses for Setup, Assignments and Quizzes; the prior null College ID TypeError is closed.

## 2026-09-24 — Phase 15 Mid Semester, Practical and Marks Entry / ADR 216

The next three Internal Assessment milestones are IMPLEMENTED / OWNER QA REQUIRED. Mid Semester and Practical extend the exact ADR 215 policy/Course Offering/Faculty Allocation/calendar/roster chain. Marks Entry consumes only the immutable publication roster, requires complete-roster ENTERED/ABSENT submission, validates component maximum marks, and audits every first entry/correction with revision numbering. Marks Approval is next after QA.

## 2026-09-24 — Academic Operations navigation and selector consistency
ADR 217 separates the College sidebar into Course Delivery, Attendance and Assessment presentation groups. This is a UI/navigation correction only and does not change the existing domain hierarchy, routes, permissions or persistence. Internal Assessment dynamic selectors now use the shared project SearchableSelect instead of browser-native selects for Course Offering, Assessment Component, Faculty Allocation and Published Activity; Assessment Type uses the same interaction for visual consistency. Compact row-level ENTERED/ABSENT state selection remains a standard select. Future Assessment, Attendance, Examination and Result pages must follow the shared searchable-select contract. Status: IMPLEMENTED / OWNER QA REQUIRED.
