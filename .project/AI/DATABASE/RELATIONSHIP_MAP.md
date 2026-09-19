# Database Relationship Map

## Purpose
This file is the compact join map for application queries and reporting. It must describe verified relationships only.

## Rules
- Never infer a relationship only because two columns share a similar name.
- Record the physical FK when one exists.
- If a legacy logical relationship exists without an FK, mark it explicitly as `logical/unconstrained` and document evidence.
- Include the tenant ownership path so queries cannot accidentally cross affiliated Colleges.

## Relationship Registry

| From Table.Column | To Table.Column | Cardinality | Required? | FK Enforced? | Delete Rule | Business Meaning |
|---|---|---|---|---|---|---|
| `role_permissions.role_id` | `roles.id` | many-to-one | Yes | Yes | RESTRICT | Permission membership of a role |
| `role_permissions.permission_id` | `permissions.id` | many-to-one | Yes | Yes | RESTRICT | Permission granted through a role |
| `user_roles.user_id` | `users.id` | many-to-one | Yes | Yes | RESTRICT | User receiving a scoped role assignment |
| `user_roles.role_id` | `roles.id` | many-to-one | Yes | Yes | RESTRICT | Role assigned to a user |
| `user_sessions.user_id` | `users.id` | many-to-one | Yes | Yes | RESTRICT | Authentication session owner |
| `audit_logs.actor_user_id` | `users.id` | many-to-one | No | Yes | SET NULL | Optional actor; audit survives account removal |
| `users.created_by_user_id` | `users.id` | many-to-one self-reference | No | Yes | SET NULL | Immutable creator provenance for access-management visibility |
| `roles.created_by_user_id` | `users.id` | many-to-one | No | Yes | SET NULL | Immutable creator provenance for role visibility |
| `user_theme_preferences.user_id` | `users.id` | one-to-one | Yes | Yes | CASCADE | Optional personal theme selection owned by a user |
| `colleges.university_id` | `universities.id` | many-to-one | Yes | Yes | RESTRICT | College affiliated with the University root |
| `colleges.principal_user_id` | `users.id` | many-to-one | No | Yes | SET NULL | Optional linked Principal/College Admin account |
| `authorized_signatories.university_id` | `universities.id` | many-to-one | Yes | Yes | RESTRICT | Governed signing appointment owned by the University |
| `academic_sessions.university_id` | `universities.id` | many-to-one | Yes | Yes | RESTRICT | University academic period |
| `degree_levels.university_id` | `universities.id` | many-to-one | Yes | Yes | RESTRICT | University degree classification |
| `degrees.university_id` | `universities.id` | many-to-one | Yes | Yes | RESTRICT | University degree ownership |
| `degrees.degree_level_id` | `degree_levels.id` | many-to-one | Yes | Yes | RESTRICT | Degree classification |
| `academic_disciplines.university_id` | `universities.id` | many-to-one | Yes | Yes | RESTRICT | University subject-domain ownership |
| `academic_disciplines.parent_id` | `academic_disciplines.id` | many-to-one | No | Yes | RESTRICT | Specialization parent discipline |
| `program_templates.university_id` | `universities.id` | many-to-one | Yes | Yes | RESTRICT | University blueprint ownership |
| `program_templates.degree_id` | `degrees.id` | many-to-one | Yes | Yes | RESTRICT | Award granted by template |
| `program_template_disciplines.program_template_id` | `program_templates.id` | many-to-one | Yes | Yes | CASCADE | Program Template side of many-to-many Discipline binding |
| `program_template_disciplines.discipline_id` | `academic_disciplines.id` | many-to-one | Yes | Yes | RESTRICT | Top-level Discipline mapped to a Program Template; application validation requires same University, active status and `kind = DISCIPLINE` |
| `program_template_discipline_specializations.program_template_discipline_id` | `program_template_disciplines.id` | many-to-one | Yes | Yes | CASCADE | Specialization belongs to one specific Template/Discipline mapping |
| `program_template_discipline_specializations.specialization_id` | `academic_disciplines.id` | many-to-one | Yes | Yes | RESTRICT | Optional mapped Specialization; application validation requires same University, active status, `kind = SPECIALIZATION`, and `parent_id` matching the mapped Discipline |
| `course_categories.university_id` | `universities.id` | many-to-one | Yes | Yes | RESTRICT | University curriculum classification ownership |

## Common Join Paths
Document frequently used, verified business join paths here as modules are migrated.

Example format only:
`student -> enrollment -> course_offering -> course`

For each real path added, specify:
- Physical tables and join columns.
- Tenant filters required.
- Effective session/batch/status filters.
- Whether the path represents current state or historical state.

Verified authorization path:
`users -> user_roles -> roles -> role_permissions -> permissions`

- Join on the foreign keys registered above.
- Filter active users, active/effective user-role assignments, active roles and active permissions.
- Evaluate each `user_roles.scope_type + scope_reference` independently; never combine a permission from one assignment with another assignment's scope.
- Current effective access grain is one user + permission + assignment scope.

Verified authentication path:
`users -> user_sessions`

- Sessions contain only a token hash, never the raw refresh/session token.
- Require active user, active session and `expires_at` in the future.

## Reporting Grain
For important fact/transaction tables, record their grain, e.g. "one row per student per class meeting". The grain prevents double counting in reports.

- `role_permissions`: one row per role-permission grant.
- `user_roles`: one row per user-role-scope assignment.
- `user_sessions`: one row per issued refresh/session token.
- `audit_logs`: one immutable row per audit event.
- `theme_policies`: one row per canonical authorization/theme scope.
- `user_theme_preferences`: zero or one row per user.
- `colleges`: one row per affiliated College under the root University.
- `authorized_signatories`: one row per University signatory appointment and authority category.
- `academic_sessions`: one row per University academic period; at most one is current.
- `degree_levels`: one row per ordered University degree classification.

Verified theme resolution path:
`users -> user_theme_preferences` plus applicable `theme_policies`

- A preference is effective only when the policy permits personal selection and RBAC grants `theme.select_own`.
- Current runtime resolves the `GLOBAL/global` policy. Future College resolution must validate Affiliated College ownership before consulting an College-scoped policy.

## Course / Subject Master relationships — implemented 2026-08-21

Implemented database relationships:

```text
universities.id
    └── courses.university_id

course_categories.id
    └── courses.course_category_id

course_types.id
    └── courses.course_type_id
```

Business view:

```text
University
├── Course Categories
│       └── Course / Subject Master
└── Course Types
        └── Course / Subject Master
```

There is intentionally **no direct Course → Discipline or Course → Specialization relationship in the Course Master**.

The next Curriculum / Course Mapping layer will provide the academic-context relationship:

```text
Program Template
    ↓
Discipline
    ↓
Specialization (optional)
    ↓
Course / Subject
    ↓
Term / Semester + curriculum-specific academic values
```

This keeps Course / Subject Master reusable across curricula and prevents curriculum-specific values from being permanently stored on the master.

## Curriculum Header Relationships — 2026-08-22
| Child FK | Parent | Cardinality | Required | Delete | Meaning |
|---|---|---|---|---|---|
| `curricula.parent_curriculum_id` | `curricula.id` | many-to-one self relationship | No | RESTRICT | Source approved Curriculum for an amendment/version link |
| `curricula.university_id` | `universities.id` | many-to-one | Yes | RESTRICT | University owner |
| `curricula.program_template_id` | `program_templates.id` | many-to-one | Yes | RESTRICT | Program blueprint being versioned |
| `curricula.academic_session_id` | `academic_sessions.id` | many-to-one | Yes | RESTRICT | Session governing the curriculum version |
| `curricula.created_by` | `users.id` | many-to-one | No | SET NULL | Creating actor |
| `curricula.updated_by` | `users.id` | many-to-one | No | SET NULL | Last updating actor |

## Curriculum Terms Relationships — 2026-08-22

| Child FK | Parent | Cardinality | Required | Delete | Meaning |
|---|---|---|---|---|---|
| `curriculum_terms.curriculum_id` | `curricula.id` | many-to-one | Yes | RESTRICT | Versioned Curriculum Header owning the ordered Term / Semester |
| `curriculum_terms.created_by` | `users.id` | many-to-one | No | SET NULL | Creating actor |
| `curriculum_terms.updated_by` | `users.id` | many-to-one | No | SET NULL | Last updating actor |


| `curriculum_slots.curriculum_term_id` | `curriculum_terms.id` | many-to-one | Yes | Yes | RESTRICT | Ordered Slot belongs to one Curriculum Term / Semester |
| `curriculum_slots.course_category_id` | `course_categories.id` | many-to-one | Yes | Yes | RESTRICT | Reuses University Course Category master without sharing Slot identity |

| `curriculum_slots.course_type_id` | `course_types.id` | many-to-one | Yes* | Yes | RESTRICT | Reuses University Course Type master as Slot mapping constraint |

*Existing Phase 1 rows may be null immediately after migration until edited; Phase 2 create/update requires Course Type.

## Curriculum Course / Paper Mapping Relationships — 2026-08-22

| Child FK | Parent | Cardinality | Required | Canonical | Delete | Meaning |
|---|---|---|---|---|---|---|
| `curriculum_course_mappings.curriculum_slot_id` | `curriculum_slots.id` | many-to-one | Yes | Yes | RESTRICT | Mapping belongs to one curriculum-specific Slot |
| `curriculum_course_mappings.course_id` | `courses.id` | many-to-one | Yes | Yes | RESTRICT | Reuses University Course / Subject Master |

| `curriculum_course_mappings.discipline_id` | `academic_disciplines.id` | many-to-one | New mappings: Yes | RESTRICT | Program Template Discipline context |
| `curriculum_course_mappings.specialization_id` | `academic_disciplines.id` | many-to-one | No | RESTRICT | Optional Program Template Specialization context |


## Curriculum Amendment Join Path — 2026-08-24
`curricula (approved source) -> curricula.parent_curriculum_id (amendment)`

The chain is intentionally single-successor in application rules. An unapproved child does not replace the current approved parent. An `ACTIVE / APPROVED` child makes its parent a Previous approved version.

## Academic Policy relationships
`universities -> academic_policies`
`academic_sessions -> academic_policies`
`degree_levels -> academic_policies` (scope-dependent)
`program_templates -> academic_policies` (scope-dependent)
`curricula -> academic_policies` (scope-dependent)
`academic_policies -> academic_policies` (parent revision / superseded version)
`academic_policies -> academic_policy_credit_completion_rules` (1:0..1)


## Academic Policy Credit / Completion — Dynamic Category Requirements
- `academic_policies.id` 1 -> 0..1 `academic_policy_credit_completion_rules.academic_policy_id`
- `academic_policies.id` 1 -> 0..N `academic_policy_credit_category_requirements.academic_policy_id`
- `course_categories.id` 1 -> 0..N `academic_policy_credit_category_requirements.course_category_id`
- Requirement rows use the same University-owned Course Category Master already used by Curriculum Slots.
- A Course Category may appear only once per Academic Policy version.

## Academic Policy Attendance
`academic_policies (1) -> (0..1) academic_policy_attendance_rules`

Rules:
- Attendance Rule belongs to exactly one Academic Policy version.
- Academic Policy may omit Attendance Rule when attendance regulation is not defined at that scope/version.
- Attendance Rule changes invalidate the Academic Policy validation checkpoint.
- Future Attendance/Examination execution must resolve the applicable ACTIVE policy by scope and read this rule; it must not hard-code attendance thresholds.

### Academic Policy Assessment / Examination
`academic_policies (1) → (0..1) academic_policy_assessment_exam_rules`

The rule is version-bound to its Academic Policy. Future Assessment Scheme / Examination / Result modules consume the resolved applicable policy; they must not duplicate these governance values.

### Academic Policy Grading
`academic_policies (1) → (0..1) academic_policy_grading_rules`
`academic_policies (1) → (0..N) academic_policy_grade_bands`

### Academic Policy Promotion / Progression
`academic_policies (1) → (0..1) academic_policy_progression_rules`

Future Student Academic Lifecycle consumes the resolved applicable rule; the policy table does not hold student decisions.

### Progression Rule Sets

`academic_policies (1) → (0..N) academic_policy_progression_rule_sets`

`academic_policy_progression_rule_sets (N) ↔ (N) curriculum_terms`
through `academic_policy_progression_rule_terms` for source Terms.

Each specific Rule Set may also reference one `target_curriculum_term_id`.

## Academic Calendar Relationships — 2026-08-25
| Child FK | Parent | Cardinality | Required | Delete | Meaning |
|---|---|---|---|---|---|
| `academic_calendars.university_id` | `universities.id` | many-to-one | Yes | RESTRICT | University owner |
| `academic_calendars.academic_session_id` | `academic_sessions.id` | one calendar per session within University | Yes | RESTRICT | Calendar's governing Academic Session |
| `academic_calendar_events.academic_calendar_id` | `academic_calendars.id` | many-to-one | Yes | RESTRICT | Event belongs to University Academic Calendar |

Verified business path:
`universities -> academic_sessions -> academic_calendars -> academic_calendar_events`

Future College calendar adoption/override must reference this University foundation and may override only events where `allow_college_override = true`.



## College Program Offering
`College -> CollegeProgramOffering -> ProgramTemplate + Curriculum + AcademicSession`

Rules:
- all academic references must resolve to the College's University
- Curriculum must match Program Template + Academic Session
- later College academic execution should reference an ACTIVE Program Offering rather than selecting University Program Template directly

## Admission Selection Rules — 2026-08-26
`college_program_reservation_plans (1) -> (many historical versions) college_admission_selection_rules`

At runtime only one Selection Rule version may be ACTIVE for an effective Intake seat bucket. Reservation Plan linkage is nullable and present only where Reservation is configured. Future Student Admission records must reference the exact Selection Rule version used so historical selection remains traceable. Each Selection Rule has ordered `college_admission_selection_rule_tiebreakers` children used by the future ranking engine; qualifying thresholds are explicit normalized Merit / Entrance / Final Weighted fields.


## Student Admission Processing — Applications
`colleges`
→ `college_admission_cycles`
→ `college_admission_applications`
→ `college_admission_application_choices`
→ `college_program_intakes` / effective `bucket_key`
→ optional `college_program_reservation_plans`
→ exact `college_admission_selection_rules` version

Future Score / Interview / Merit / Seat Allocation records must reference the Application Choice and consume its locked Selection Rule version rather than resolving a new current rule.

### Admission Cycle Program Offering anchor (ADR 022)
`college_program_offerings.id`
→ `college_admission_cycles.college_program_offering_id`
→ `college_admission_applications.college_admission_cycle_id`
→ `college_admission_application_choices`

Rule: every Application Choice Intake/seat bucket must resolve back to the same `college_program_offering_id` carried by the Admission Cycle. `academic_session_id` on Admission Cycle is a derived compatibility snapshot, not an independent academic parent.

## Student Admission Processing — Interview
`college_admission_application_choices` -> one `college_admission_interviews` -> many `college_admission_interview_evaluators`.
Interview retains the Application Choice's exact locked Selection Rule and writes its completed normalized component into the existing `college_admission_scores` context.

## Admission Form Stage 1 relationships — 2026-08-27
`University -> Admission Form Template -> Steps -> Fields -> Field Options`

`University/College + optional Degree Level/Degree/Program Template/Program Offering/Admission Cycle -> Form Mapping -> Form Template`

`University/College + optional Degree Level/Degree/Program Template/Program Offering/Admission Cycle -> Application Fee Rule`

`College Admission Application -> snapshotted Form Template + snapshotted Fee Rule/Amount -> Dynamic Field Values`

Existing authoritative transaction chain remains:
`College -> Admission Cycle -> Application -> Application Choice -> Intake/Seat Bucket -> [Selection Rule for REGULAR] -> Eligibility/Score/Interview -> Merit/Roster`

DIRECT admission intentionally keeps `college_admission_selection_rule_id` nullable and bypasses Selection Rule-driven Score/Interview/Merit while remaining linked for future Seat Allocation/Admission Approval/Student Enrollment.

### Stage 1 Admission Form authorization — existing RBAC
`Permission -> Role -> scoped UserRole -> User -> College`

- `college_admission_form.view`, `college_admission_form.manage`, `college_admission_form.map`, and `college_application_fee.manage` live in the common Permission catalog.
- University `SUPER_ADMIN` receives them by default.
- Custom roles receive them only through the existing Role → Permissions workflow.
- College runtime authorization requires the permission through an active `user_roles` assignment scoped to the same College.
- No separate Admission Form role allow-list or College enable flag participates in authorization.

### Admission Form field conditional/applicability relationships — 2026-08-27
- `college_admission_form_field_conditions.college_admission_form_field_id` → `college_admission_form_fields.id` (target; CASCADE).
- `college_admission_form_field_conditions.source_field_id` → `college_admission_form_fields.id` (source; RESTRICT).
- `college_admission_form_field_scopes.college_admission_form_field_id` → `college_admission_form_fields.id` (CASCADE).
- `college_admission_form_field_scopes.degree_level_id` → `degree_levels.id` (RESTRICT).
- `college_admission_form_field_scopes.degree_id` → `degrees.id` (RESTRICT).
- `college_admission_form_field_scopes.program_template_id` → `program_templates.id` (RESTRICT).
- `college_admission_form_field_scopes.college_program_offering_id` → `college_program_offerings.id` (RESTRICT).
- `college_admission_form_field_scopes.curriculum_id` → `curricula.id` (RESTRICT).
- `college_admission_form_field_scopes.college_admission_cycle_id` → `college_admission_cycles.id` (RESTRICT).

### Admission Form governance/current Curriculum
University Admission Form Template (`college_admission_form_templates`, college_id NULL)
→ `allow_college_override = true`
→ College Admission Form Template (`parent_template_id`)
→ College-specific steps/fields
→ Application Form resolver.

Field applicability `curriculum_id`
→ `curricula.id`
→ only current approved ACTIVE Curriculum is valid for new configuration; approved superseded ancestors remain history only.

## Admission Seat Allocation / Consumption — 2026-09-03
`college_admission_merit_entries (1) -> (0..1) college_admission_seat_allocations`

Each Seat Allocation also RESTRICT-references the same:
- College
- College Program Intake
- effective `bucket_key`
- Application
- Application Choice
- Score
- exact Selection Rule version
- optional locked Reservation Plan

Physical seat:
`college_admission_seat_allocations.physical_reservation_category_id -> reservation_categories.id` is nullable; NULL means Open / Unreserved, non-NULL must be a configured Vertical category from the exact Reservation Plan.

Horizontal overlay:
`college_admission_seat_allocations (1) -> (0..N) college_admission_seat_allocation_horizontal_categories -> reservation_categories`

Horizontal rows do not consume physical capacity. `fulfills_target` is independent from actual category applicability.

Operational chain:
`Merit Entry -> Seat Allocation -> future Admission Confirmation -> Student Lifecycle`.
Admission Confirmation must reference the ACTIVE Seat Allocation instead of recalculating capacity or reservation.

### Admission verification/allocation chain — 2026-09-03
`college_admission_applications (1) -> (0..1) college_admission_document_verifications`
`college_admission_document_verifications (1) -> (0..n) college_admission_document_verification_items`
`college_admission_application_field_values (1) -> (0..1) college_admission_document_verification_items`
`college_admission_document_verifications (1 VERIFIED) -> (0..n historical) college_admission_seat_allocations`

Runtime business rule: an ACTIVE Seat Allocation may be created only from a VERIFIED application verification. The allocation retains the exact verification FK with RESTRICT delete behavior.

## Admission Confirmation / Approval — 2026-09-04
`college_admission_seat_allocations (1) -> (0..1) admissions`

Each `admissions` row RESTRICT-references the exact:
- College + Intake
- Admission Application + Application Choice
- VERIFIED Document Verification
- Seat Allocation
- Merit Entry
- normalized Score
- locked Selection Rule version

Operational chain is now:
`Merit Entry -> VERIFIED Document Verification -> ACTIVE Seat Allocation -> CONFIRMED Admission -> future Student Enrollment / Lifecycle`.

Admission Confirmation does not recalculate capacity/reservation. A CONFIRMED Admission blocks Seat Allocation cancellation. A REVOKED Admission preserves history and leaves the physical Seat Allocation untouched until an explicit Seat Allocation action occurs.

## College Batch Management — 2026-09-04
`colleges.id`
-> `college_program_offerings.college_id`
-> `batches.college_program_offering_id`
-> `sections.batch_id`
-> future `student_enrollments.section_id` / `student_enrollments.batch_id`

Batch inherits Program Template, Curriculum and Academic Session from the exact Program Offering. It does not duplicate or independently select those masters. Batch activation requires the parent Offering and its Intake to be ACTIVE; Batch never recalculates Intake/Reservation/Seat Allocation capacity.


## College Section Management — 2026-09-04
`college_program_offerings.id -> batches.college_program_offering_id -> sections.batch_id`

Section inherits College, Program Template, Curriculum, Academic Session and Intake context through Batch -> Program Offering. It does not duplicate those FKs or define independent admission capacity.

Future Student Enrollment should reference the exact Batch and Section assignment consistently; Section must belong to that Batch.


## College Academic Calendar Relationships — 2026-09-04
| Child FK | Parent | Cardinality | Required | Delete | Meaning |
|---|---|---|---|---|---|
| `college_academic_calendars.college_id` | `colleges.id` | many-to-one | Yes | RESTRICT | College owner |
| `college_academic_calendars.university_academic_calendar_id` | `academic_calendars.id` | one adoption per College + University Calendar | Yes | RESTRICT | Authoritative University calendar source |
| `college_calendar_overrides.college_academic_calendar_id` | `college_academic_calendars.id` | many-to-one | Yes | RESTRICT | College calendar receiving override |
| `college_calendar_overrides.academic_calendar_id` | `academic_calendars.id` | many-to-one | Yes | RESTRICT | Exact University Calendar snapshot/source |
| `college_calendar_overrides.academic_calendar_event_id` | `academic_calendar_events.id` | one override per College Calendar + University event | Yes | RESTRICT | Exact University event being overridden |

Effective path: `University Academic Calendar -> University Event -> College Calendar adoption -> optional allowed College Override`. A College override is valid only while the University event is ACTIVE and `allow_college_override = true`; it never changes the University row.

## Fee Foundation — ADR 095
- `universities -> fee_heads` (University-owned where `college_id` is NULL)
- `colleges -> fee_heads` (College-owned where `college_id` is set)
- `universities -> fee_structures`
- `colleges -> fee_structures` for College-owned structures
- `academic_sessions -> fee_structures`
- `program_templates -> fee_structures` optional University scope / derived College context
- `college_program_offerings -> fee_structures` required for College-owned structures
- `fee_structures -> fee_structure_items`
- `fee_heads -> fee_structure_items`

Future `fee_demands` will snapshot applicable ACTIVE University + College structures rather than mutating these setup rows.


## Fee Foundation relationships — 2026-09-04
- `fee_categories.university_id -> universities.id`
- `fee_categories.college_id -> colleges.id` (nullable; null = University-owned category)
- `fee_heads.fee_category_id -> fee_categories.id` (authoritative classification)
- College Fee Heads may reference an ACTIVE University-owned category or an ACTIVE category owned by the same College.
- Fee Category has no Degree/Program FK; Program-wise amount/applicability remains under Fee Structure.


### Fee applicability
- `fee_structures.college_applicability` is populated for University-owned structures (`MANDATORY` / `OPTIONAL`) and null for College-owned structures.
- `college_fee_structure_adoptions.university_fee_structure_id -> fee_structures.id` records a College decision only for OPTIONAL University structures.
- `college_fee_structure_adoptions.college_id -> colleges.id`.
- Mandatory University structures do not require adoption rows; they are effective automatically for matching ACTIVE College Program Offerings.

### Fee Head inheritance into College Fee Structures — ADR 098
- `fee_heads.university_id` always identifies the University fee domain.
- `fee_heads.college_id = NULL` means University-owned/inherited Fee Head.
- `fee_heads.college_id = <college>` means College-local Fee Head.
- `fee_structure_items.fee_head_id` for a College-owned Fee Structure may reference either an ACTIVE same-University University Fee Head or an ACTIVE Fee Head owned by that exact College.
- College-owned Fee Structures must never reference a Fee Head owned by another University or another College.
- Inheritance does not copy rows; the College references the authoritative University Fee Head directly.

### Fee collection period relationship (ADR 099)
`ProgramTemplate(term_structure,duration_terms) -> Curriculum -> CurriculumTerm -> CollegeProgramOffering -> FeeStructure(charge_basis,charge_period_no)`.
`charge_basis` controls financial recurrence only. `charge_period_no` is used only for specific term/year modes; College specific-term values must correspond to an ACTIVE CurriculumTerm sequence on the offering's Curriculum.

### Fee recurring period rates
`Program Template -> Curriculum -> Curriculum Terms -> College Program Offering -> Fee Structure -> Fee Structure Item -> Fee Structure Item Period Amounts`

For College fee setup, Curriculum Terms are the operational source of available academic periods. `fee_structure_item_period_amounts.period_no` is a term sequence under `PER_TERM` or a derived academic-year number under `PER_ACADEMIC_YEAR`; the parent Fee Structure defines that meaning.

### Fee recurring-period children
`fee_structure_items 1 ── * fee_structure_item_period_amounts` — optional amount override for an applicable billing period.
`fee_structure_items 1 ── * fee_structure_item_period_exclusions` — explicit Not Applicable marker for a billing period.
For College recurring structures, valid period numbers are derived from ACTIVE Curriculum Terms of the exact College Program Offering; Academic Year is only a billing grouping over those Curriculum term sequences.

### Period-first Fee Structure configuration — ADR 102
Canonical user-facing hierarchy:
`Fee Structure -> Derived Billing Period -> Effective Fee Items`

Storage remains normalized as:
`fee_structures 1 -- * fee_structure_items 1 -- * fee_structure_item_period_amounts`
`fee_structure_items 1 -- * fee_structure_item_period_exclusions`

The Billing Period is derived, not independently mastered. For College structures it is derived from the exact Offering Curriculum; for University structures it is a Program Template period template until applied to a College Offering. Adding the same Fee Head to another period updates its period applicability/amount children rather than creating a duplicate Fee Structure Item.

### Period-first applicability presentation — ADR 104
The visible Fee Setup relationship is `Billing Period -> Fee Item`. Presence in a Billing Period means applicable. The existing `fee_structure_item_period_amounts` / `fee_structure_item_period_exclusions` relations remain normalized persistence details for recurring structures and must not be exposed as a second applicability concept in the standard UI.

### Fee Structure → Curriculum (ADR 105)
`fee_structures.curriculum_id -> curricula.id` (nullable, RESTRICT)
- Required by service validation for University `PER_TERM`, `PER_ACADEMIC_YEAR`, `SPECIFIC_TERM`, `SPECIFIC_ACADEMIC_YEAR` structures.
- `ONE_TIME` may remain null.
- University Billing Periods are projections of ACTIVE `curriculum_terms`; Fee Management does not create academic Semester/Year identities.
- College structures obtain Curriculum through `college_program_offerings.curriculum_id`.


### Fee Structure → Curriculum current-version invariant
For University recurring/specific academic-period Fee Structures:
`fee_structures.curriculum_id` must be selected from the current Curriculum version only (`ACTIVE + APPROVED + no APPROVED successor`). Billing Periods derive from that Curriculum's ACTIVE `curriculum_terms`. Previous/superseded Curriculum versions remain historical references only and are not valid for new Fee configuration.

### Fee recurring charge policy
`fee_structure_items 1 → N fee_structure_item_period_settings`

`fee_structure_item_period_settings` is unique by `(fee_structure_item_id, period_no)` and stores the exact recurring billing-period policy: mandatory, enrollment-clearance requirement, installment permission, display order and status. Period amount remains in `fee_structure_item_period_amounts`; period absence remains represented by the period-first applicability model. ONE_TIME uses the parent Fee Structure Item fields.

### Applicable Fee Demand
`CONFIRMED Admission -> Fee Demand (Admission + Billing Period) -> Fee Demand Items (snapshotted effective University/College Fee Structure Items) -> future Payment/Adjustment -> Fee Clearance -> Student Enrollment`.

### Applicable Fee Demand
`CONFIRMED Admission -> Fee Demand (Admission + Billing Period) -> Fee Demand Items (snapshotted effective University/College Fee Structure Items) -> future Payment/Adjustment -> Fee Clearance -> Student Enrollment`.

## Fee Demand workflow linkage — ADR 115
`Admission (CONFIRMED)` → auto resolve effective University/College Fee Structures → `FeeDemand (Period 1, ADMISSION_AUTO)` → `FeeDemandItems` snapshot amount + Mandatory + Enrollment Clearance Required + Installment Allowed.

Later-period path is intentionally dependent on academic progression:
`CollegeProgramOffering` → exact `Curriculum` / Term → auto-resolved `AcademicPolicy` → future authoritative Student Academic Progression result → eligible students → future `FeeDemand (BULK_PERIOD)`.

Academic Policy is not duplicated at College level. Resolution precedence is exact Curriculum > Program Template > Degree Level > University within the same University and Academic Session, using current ACTIVE + APPROVED policy state and effective dates.

Admission revocation may cancel untouched Fee Demands. Payment/adjustment activity becomes a financial dependency that blocks direct Admission revocation until future reversal/settlement processing is completed.

## Fee Demand context resolution — ADR 118
`College Program Offering → effective University/College Fee Structures → Purpose + Collection Basis → Fee-derived Billing Context → eligible Admission/Student cohort → Fee Demand → Fee Demand Items`.

Admission Initial consumes ADMISSION charges plus first-period ACADEMIC items marked Enrollment Clearance Required. First Academic period bulk may use CONFIRMED admissions before Student Enrollment exists. Later Academic periods consume authoritative Academic Progression eligibility under the automatically resolved University Academic Policy. Fee Demand does not own a duplicate Semester/Academic-Year master.

## Curriculum Academic Period Relationships - 2026-09-12

- `academic_calendar_term_periods.academic_calendar_id` -> `academic_calendars.id` (many-to-one, required, cascade on delete).
- `academic_calendar_term_periods.curriculum_term_id` -> `curriculum_terms.id` (many-to-one, required, restrict on delete).
- `academic_calendar_events.academic_calendar_term_period_id` -> `academic_calendar_term_periods.id` (many-to-one, optional, null on delete).
- An Academic Period Term must belong to a current approved Curriculum for the Calendar's University and Academic Session.

## Academic Period to Fee Lifecycle - 2026-09-12

This is an enforced semantic/snapshot relationship rather than a direct foreign
key from every finance record:

1. `academic_calendar_term_periods.curriculum_term_id` identifies the dated Curriculum Term.
2. Fee Setup resolves that Term's sequence into `fee_structure_item_period_settings.period_no` and validates its `due_date` inside the Calendar Period.
3. Demand generation copies the context into `fee_demands.billing_period_no` / `billing_period_label` and `fee_demand_items.source_period_no` / `due_date`.
4. `fee_student_benefits.fee_demand_id` and `fee_student_benefit_items.fee_demand_item_id` apply Benefits to that snapshotted liability.
5. `fee_installment_schedules.fee_demand_item_id` creates an alternate due schedule without changing the source academic period.
6. `fee_late_fine_charges` reference the Demand Item and optional Installment and snapshot the due date used for calculation.
7. `fee_payment_allocations` reference the Demand/Demand Item and optional Installment/Late Fine and snapshot the due date consumed by collection.

Calendar changes therefore govern new Fee Setup validation but do not silently
rewrite existing Demands, Benefits, Installments, Late Fines, or Payments.

## Student Fee Ledger projection — ADR 189
`Admission -> Fee Demands -> Fee Demand Items` supplies principal debit rows.

`Fee Demand -> APPROVED Student Benefit -> Benefit Items` supplies adjustment credit rows.

`Fee Demand Item/Installment -> ACTIVE Late Fine Charge` supplies penalty debit rows.

`Fee Payment -> Payment Allocations -> Demand Item / Installment / Late Fine` supplies collection credit rows.

The Student Fee Ledger is a projection across these existing relationships. No `fee_ledgers` table exists or is required. `Admission::feeDemands()` is the canonical Eloquent parent relation used for scoped ledger/student discovery.

## ADR 190 — Fee Adjustment / Refund relationships
`FeeDemand -> FeeDemandItem -> FeeAdjustment`

`FeePayment -> FeePaymentAllocation -> FeePaymentRefundAllocation <- FeePaymentRefund`

Each Refund Allocation also snapshots direct FKs to Fee Demand, Fee Demand Item, optional Installment Schedule, and optional Late Fine Charge. This preserves provenance and allows exact balance restoration without rewriting the original payment allocation.

## ADR 197 — Fee Clearance derived relationship (2026-09-16)
Fee Clearance introduces **no new physical foreign-key relationship**.

Logical projection path:
`College Admission -> Fee Demand -> enrollment-clearance-required Fee Demand Items -> Benefits / Payment Allocations / Adjustments / Reversals / Refunds -> derived Fee Clearance -> Student Enrollment gate`.

The permission-registration migration uses the already-documented authorization relationships `permissions -> role_permissions <- roles`; it adds rows only and does not alter those relationships.

## Student Enrollment Foundation — ADR 199
`users (0..1) -> students.user_id` (one reusable portal identity; imported Students may initially have none)

`college_admission_applications (0..1) -> students.college_admission_application_id`

`admissions (0..1) -> students.admission_id`

`students (1) -> (0..N) student_enrollments`

`college_program_offerings (1) -> (0..N) student_enrollments`

`batches (0..1) -> student_enrollments.batch_id`

`sections (0..1) -> student_enrollments.section_id`

`students (1) -> (0..N) student_profile_values`

`college_admission_form_fields (0..1) -> student_profile_values.source_application_field_id`

`students (0..1) -> applicant_profiles.student_id`

Programme Offering carries Academic Session. Student identity therefore remains stable while Enrollment carries the session/programme context. Batch/Section hierarchy consistency is a service-level validation requirement in later enrollment/assignment milestones, not merely an FK check.

## ENR-3 / ADR 203
`colleges 1—1 student_identity_settings`; `colleges 1—N student_identity_sequences`. Permanent Student UID/University Roll live on `students`; enrollment-specific Class Roll lives on `student_enrollments`. Exam Roll is outside ENR-3.

### ENR-3.3 Class Roll scope
`student_identity_settings.class_roll_scope` selects Programme Offering or Discipline sequencing. Discipline scope is derived through `student_enrollments.admission_id → admissions → college_admission_applications → academic preference → discipline_id`; no duplicate discipline ownership is added to Student/Enrollment.

### ENR-4 Discipline normalization
`academic_disciplines (0..1) -> student_enrollments.discipline_id`. For new ADMISSION enrollments this snapshots the already-authoritative Application Academic Preference Discipline; for IMPORT enrollments it is resolved from mapped `discipline_code`. Student Identity consumes Enrollment Discipline first, with legacy Admission preference fallback for pre-ENR-4 rows.

## ADR 206 — Canonical Enrollment Academic Context — 2026-09-18
Both Student entry routes converge before downstream academic processing:

`Application -> Admission -> Student -> Student Enrollment <- Student Import`

Canonical downstream relationships:
- `student_enrollments.curriculum_id -> curricula.id`
- `student_enrollments.discipline_id -> academic_disciplines.id`
- `student_enrollments.specialization_id -> academic_disciplines.id`
- `student_enrollment_course_choices.student_enrollment_id -> student_enrollments.id`
- `student_enrollment_course_choices.curriculum_term_id -> curriculum_terms.id`
- `student_enrollment_course_choices.curriculum_slot_id -> curriculum_slots.id`
- `student_enrollment_course_choices.curriculum_course_mapping_id -> curriculum_course_mappings.id`
- `student_enrollment_course_choices.course_id -> courses.id`

For legacy ADMISSION rows only, normalization source is:
`student_enrollments.admission_id -> admissions -> college_admission_applications -> college_admission_application_academic_preferences / college_admission_application_course_choices -> canonical Enrollment context`.

Application/Admission remains provenance/history after normalization. Attendance, Examination, Result, Marksheet, Promotion, Registration and Student Profile must consume Student + Enrollment academic context rather than provenance-specific branches.

### ENR-6 Student Profile consumption rule (2026-09-18)
Student Profile reads `students (1) -> (0..N) student_profile_values` and `students (1) -> (0..N) student_enrollments -> student_enrollment_course_choices`. Profile editing may mutate Student core/profile values only; identity and Enrollment academic relationships are displayed but remain owned by Student Identity/Enrollment modules. `source_type` is provenance only.
