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
