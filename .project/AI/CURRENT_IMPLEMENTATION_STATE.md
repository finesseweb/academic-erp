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


### 2026-08-27 — Stage 1 RBAC alignment correction (authoritative)
- Removed the temporary Admission Form College enable/disable gate and per-College allowed-role checklist.
- Admission Form Setup now follows the same Access Management hierarchy as other ERP modules.
- `SUPER_ADMIN` has the feature by default.
- Any custom role may be granted the active College-delegable Admission Form permissions through the existing Role → Permissions matrix.
- College menu visibility and backend access resolve automatically from College-scoped RBAC; no separate checkbox configuration is required.
- Template governance remains configuration behavior only and does not create a second authorization system.

### Stage 1 enhancement — Conditional Fields & Academic Applicability — 2026-08-27

Status: **OWNER_QA_REQUIRED**

- Answer-based dynamic field conditions implemented in builder + Application renderer + Laravel validation.
- Academic applicability implemented against canonical Degree Level → Degree → Program → Program Offering → Curriculum → Admission Cycle references.
- Non-applicable fields are server-filtered from the generated form.
- Required conditional fields are enforced only while visible.
- Hidden/scoped-out stale responses are removed on draft save.
- University base conditions/scopes are inherited; College extension conditions may reference inherited base fields.
- This remains inside the documented Stage 1 prerequisite branch. Frozen hierarchy resume point is unchanged: Stage 1 QA → Interview QA → Merit/Roster.

### Stage 1 governance correction — 2026-08-27
Admission Form Setup now uses explicit University-first override governance aligned with Academic Calendar. `allow_college_override` is OFF by default; College extension creation requires it to be ON plus normal College RBAC permission. Curriculum applicability selectors and validation use only the current approved ACTIVE Curriculum version after amendment/successor resolution. Stage 1 remains in OWNER_QA_REQUIRED; frozen hierarchy resume point is unchanged.

### Stage 1 QA correction — condition runtime normalization (2026-08-27)
Answer-based dynamic conditions now resolve display labels and persisted choice values canonically across public/internal renderers and backend validation. Existing Caste Category conditions do not need to be recreated.

### Public Admission Form visual refinement — 2026-08-27
The generated/public Admission Application renderer now has a form-only premium visual treatment. All controls use the existing theme token system, so institution/theme color changes continue to flow through automatically. Text/select/textarea/file controls, radio/checkbox choice surfaces, panel spacing, validation text, responsive grids and step navigation are visually aligned. This is presentation-only: template resolution, conditions, academic applicability, required-when-visible validation, file submission, public cycle gate, fee snapshot and Application/Application Choice creation remain unchanged. Stage 1 remains OWNER_QA_REQUIRED.
