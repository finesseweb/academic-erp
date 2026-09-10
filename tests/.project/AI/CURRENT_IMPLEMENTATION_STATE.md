
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
Implemented, QA pending. Payment Gateway setup now models provider-specific credential contracts instead of forcing one generic Merchant/Product pattern. Razorpay/Cashfree/PayU permit credential-only Fee Head routing; NTT DATA/Atom supports profile/default Product ID with Fee Head override. Actual external checkout/order/callback adapters are not yet implemented and must not be marked live until provider-specific end-to-end QA passes.
