# Schema Catalog

## Purpose
This is the AI's high-level map of the College ERP database. It must be kept synchronized with the implemented schema.

Do not invent table entries. Add an entry only after inspecting/approving the real schema.

## Table Catalog

| Logical Entity | Physical Table | Domain | Scope | Primary Key | Important Business Key(s) | Main Parent/Owner | Table Spec |
|---|---|---|---|---|---|---|---|
| User account | `users` | Authentication | Global identity | `id` | `email`; optional `username` | Platform | `TABLE_SPECS/users.md` |
| Role | `roles` | Authorization | Global or Affiliated-College-owned | `id` | `code` | Canonical owner scope reference | `TABLE_SPECS/roles.md` |
| Permission | `permissions` | Authorization | Global | `id` | `code`; (`resource`, `action`) | Platform permission catalog | `TABLE_SPECS/permissions.md` |
| Role permission assignment | `role_permissions` | Authorization | Inherits role ownership | (`role_id`, `permission_id`) | Same as PK | Role | `TABLE_SPECS/role_permissions.md` |
| Scoped user role assignment | `user_roles` | Authorization | Explicit scope | `id` | (`user_id`, `role_id`, `scope_type`, `scope_reference`) | User + role + scope | `TABLE_SPECS/user_roles.md` |
| User authentication session | `user_sessions` | Authentication | Inherits user authorization | `id` | `token_hash` | User | `TABLE_SPECS/user_sessions.md` |
| Immutable audit event | `audit_logs` | Audit / System | Global or explicit scope | `id` | Event ID | Optional actor + explicit scope | `TABLE_SPECS/audit_logs.md` |
| Theme policy | `theme_policies` | Appearance | Canonical global/future Affiliated College scope | `id` | (`scope_type`, `scope_reference`) | Platform or future Affiliated College scope | `TABLE_SPECS/theme_policies.md` |
| User theme preference | `user_theme_preferences` | Appearance | Own user account | `user_id` | One row per user | User | `TABLE_SPECS/user_theme_preferences.md` |
| University profile | `universities` | University Foundation | University root | `id` | `code` | Platform | `TABLE_SPECS/universities.md` |
| Affiliated College | `colleges` | University Foundation | University-owned child | `id` | `code` | `universities.id` | `TABLE_SPECS/colleges.md` |
| Authorized signatory appointment | `authorized_signatories` | University Foundation | University-owned child | `id` | Appointment identity + authority period | `universities.id` | `TABLE_SPECS/authorized_signatories.md` |
| Academic session | `academic_sessions` | Academic Master | University-owned child | `id` | (`university_id`, `code`) | `universities.id` | `TABLE_SPECS/academic_sessions.md` |
| University academic calendar | `academic_calendars` | Academic Calendar | University-owned child | `id` | (`university_id`, `academic_session_id`); (`university_id`, `code`) | `academic_sessions.id` | `TABLE_SPECS/academic_calendars.md` |
| University academic calendar event | `academic_calendar_events` | Academic Calendar | Via Academic Calendar -> University | `id` | event identity | `academic_calendars.id` | `TABLE_SPECS/academic_calendar_events.md` |
| Degree level | `degree_levels` | Academic Master | University-owned child | `id` | (`university_id`, `code`) | `universities.id` | `TABLE_SPECS/degree_levels.md` |
| Degree | `degrees` | Academic Master | University-owned child | `id` | (`university_id`, `code`) | `degree_levels.id` | `TABLE_SPECS/degrees.md` |
| Discipline / specialization | `academic_disciplines` | Academic Master | University-owned hierarchy | `id` | (`university_id`, `code`) | `universities.id`; optional self parent | `TABLE_SPECS/academic_disciplines.md` |
| Program template | `program_templates` | Academic Master | University-owned child | `id` | (`university_id`, `code`) | `degrees.id` | `TABLE_SPECS/program_templates.md` |
| Program template discipline mapping | `program_template_disciplines` | Academic Master | Program-template mapping | `id` | (`program_template_id`, `discipline_id`) | `program_templates.id`; `academic_disciplines.id` | `TABLE_SPECS/program_template_disciplines.md` |
| Program template discipline specialization mapping | `program_template_discipline_specializations` | Academic Master | Nested template/discipline mapping | `id` | (`program_template_discipline_id`, `specialization_id`) | `program_template_disciplines.id`; `academic_disciplines.id` | `TABLE_SPECS/program_template_discipline_specializations.md` |
| Course category | `course_categories` | Academic Master | University-owned child | `id` | (`university_id`, `code`) | `universities.id` | `TABLE_SPECS/course_categories.md` |

## Domain Grouping
Maintain tables under clear ERP domains as they are discovered, for example:
- Platform / Tenant
- Authentication / Authorization
- Student
- Admissions
- Academic Master
- Program / Curriculum
- Enrollment / Registration
- Attendance
- Examination / Result
- Fees / Finance
- Staff / HR
- Communication
- Documents
- Audit / System

These are organizational categories, not permission to create tables that do not yet exist.

## Required Entry Information
For every table, maintain enough information so another AI agent can quickly determine:
- What the table represents.
- Whether it is global or tenant-scoped.
- Its primary/business keys.
- The most important parent relationship.
- Where to read detailed columns, relationships and indexes.

## courses — implemented 2026-08-21

`courses` is the University-owned reusable Course / Subject Master.

Core columns:
- `id`
- `university_id`
- `course_category_id`
- `course_type_id`
- `name`
- `code`
- `description`
- `display_order`
- `status`
- timestamps

Key rules:
- Course code is unique within a University.
- Category and Type are required.
- Program/Discipline/Specialization, term, credits and L-T-P are not stored directly in this master.

- `curricula` — University-owned versioned Curriculum Header linked to Program Template and Academic Session; lifecycle DRAFT/ACTIVE/RETIRED; self-linked amendment/version chain through `parent_curriculum_id`. See `TABLE_SPECS/curricula.md`.

- `curriculum_terms` — ordered Terms / Semesters for one versioned Curriculum Header. See `TABLE_SPECS/curriculum_terms.md`.

| Curriculum slot | `curriculum_slots` | Curriculum Structure | Curriculum Term child | `id` | (`curriculum_term_id`, `display_order`) | `curriculum_terms.id`, `course_categories.id` | `TABLE_SPECS/curriculum_slots.md` |

- `curriculum_slots` Phase 2 adds Course Type and Mandatory/Choice selection rules; no Slot Credit is stored.

- `curriculum_course_mappings` — maps reusable Course / Subject Master records to curriculum-specific Slots; mapping order is deferred. See `TABLE_SPECS/curriculum_course_mappings.md`.

- `curriculum_slots.credits` — canonical curriculum/version-specific Slot Credit. Credit Summary is derived later.


## Curriculum Amendment Schema Update — 2026-08-24
`curricula` now supports controlled post-approval versioning with `parent_curriculum_id`, `revision_type`, `revision_reason`, and `revision_effective_from`. Current/Previous remains a derived business state rather than a mutable database flag.

## Academic Policies Phase 1
- `academic_policies` — versioned policy header and scope; supports UNIVERSITY, DEGREE_LEVEL, PROGRAM_TEMPLATE and CURRICULUM scope with nullable governed scope foreign keys.
- `academic_policy_credit_completion_rules` — optional one-to-one general Credit / Completion rule section; contains total-credit/CGPA/duration/transfer/exemption controls only.
- `academic_policy_credit_category_requirements` — dynamic per-policy Course Category completion thresholds (minimum credits, optional maximum credits, display order). No Major/Minor/etc. columns are hard-coded.

### Academic Policies Phase 2
- `academic_policy_attendance_rules` — optional one-to-one Attendance Policy section for an Academic Policy version; stores attendance threshold, calculation level, condonation controls, exam-eligibility requirement, special exemption permission, rounding rule, and notes.
- `attendance_registers` — one policy-bound DRAFT/FINALIZED operational register per dated Class Schedule, with audited correction revision metadata.
- `attendance_records` — one raw attendance status per canonical Student Enrollment per Attendance Register; no fee or eligibility decision is stored here.

### academic_policy_assessment_exam_rules
One-to-one Assessment / Examination governance configuration for `academic_policies`. Stores general pass/absence/grace/re-attempt permissions. Component-specific structures are intentionally deferred to configurable Assessment Scheme masters.

### academic_policy_grading_rules / academic_policy_grade_bands
Version-bound grading configuration with dynamic percentage-to-grade bands.

### academic_policy_progression_rules
One-to-one, version-bound Academic Policy configuration for promotion/progression thresholds and controlled carry-forward/detention/year-back/re-admission permissions.

### academic_policy_progression_rule_sets / academic_policy_progression_rule_terms

Dynamic version-bound progression checkpoints. Multiple source Curriculum Terms can be mapped to one target Term. This replaces legacy `academic_policy_progression_rules`.


## Academic Calendar — 2026-08-25
- `academic_calendars` — one official University Calendar header per Academic Session.
- `academic_calendar_events` — dated University calendar events/ranges; includes the future College override governance flag.
- Calendar dates are constrained by application validation to the owning Academic Session.
- Routine/Timetable and detailed Examination scheduling are intentionally separate domains.

- `college_program_offerings` — College adoption of one University Program Template + approved Curriculum for an Academic Session; operational parent for later College academic setup.

### college_admission_selection_rules
Versioned College Merit / Roster / Selection configuration attached to an exact effective Intake seat bucket; `college_program_reservation_plan_id` is optional and used only when Reservation is configured for that bucket. Supports MERIT / ENTRANCE / INTERVIEW / COMBINED selection with Merit, Entrance and Interview normalized scoring components, component-aware qualifying thresholds, optional final weighted threshold, policy/roster reference, structured ordered tie-break children and INACTIVE/ACTIVE/RETIRED lifecycle. See `TABLE_SPECS/college_admission_selection_rules.md`, `TABLE_SPECS/college_admission_selection_rule_tiebreakers.md`, ADR 018, ADR 019 and ADR 020.


### `college_admission_selection_rule_tiebreakers`
Ordered machine-readable tie-break criteria for one Selection Rule version, including Interview Score. Priority controls evaluation order; free-text policy notes are not executable. See `TABLE_SPECS/college_admission_selection_rule_tiebreakers.md`, ADR 019 and ADR 020.


## Admission Applications — 2026-08-26
- `college_admission_applications` — College/Cycle-scoped candidate application header and DRAFT/SUBMITTED/WITHDRAWN lifecycle.
- `college_admission_application_choices` — ordered Program/seat-bucket choices with optional Reservation context, exact Selection Rule version, and per-choice preliminary eligibility.

## Admission Cycle Program Offering anchor — 2026-08-26
`college_admission_cycles` now includes `college_program_offering_id` (FK -> `college_program_offerings.id`) as its authoritative parent. `academic_session_id` is retained as a derived compatibility snapshot. See ADR 022 and the table spec for lifecycle/application constraints.

## Admission Interviews — 2026-08-27
- `college_admission_interviews` — one schedule/evaluation per interview-required Application Choice.
- `college_admission_interview_evaluators` — College-user panel members and normalized evaluator scores.

## Admission Form Configuration & Internal Entry — Stage 1 — 2026-08-27
- `college_admission_form_templates` — University/College-owned form template header with optional University-parent inheritance, assigned manager, governance mode and REGULAR/DIRECT/BOTH applicability.
- `college_admission_form_steps` — ordered dynamic form steps.
- `college_admission_form_fields` — typed dynamic inputs with validation/visibility configuration.
- `college_admission_form_field_options` — stable options for dropdown/radio/checkbox/multi-select.
- `college_admission_form_mappings` — inheritance/override mappings from University/College through Degree Level/Degree/Program/Offering/Cycle.
- `college_application_fee_rules` — scoped Application Fee rules including FREE/no-fee.
- `college_admission_application_field_values` — application-specific dynamic answers/document metadata.
- `college_admission_applications` extended with `college_admission_form_template_id`, `admission_mode`, resolved Application Fee snapshot, fee rule id and `form_snapshot`.
See ADR 024 and the related TABLE_SPECS.

### Admission Form access — RBAC-native correction
No dedicated Admission Form feature-gate table is authoritative. The temporary `college_admission_form_access_controls` and `college_admission_form_access_roles` tables introduced during Stage 1 iteration are removed by migration `2026_08_27_100040_align_admission_form_access_with_rbac.php`.

Admission Form access uses existing `permissions`, `roles`, `role_permissions`, and scoped `user_roles`.


### Admission Form conditional/applicability extension — 2026-08-27
- `college_admission_form_field_conditions` — relational dynamic-field answer dependencies. See `TABLE_SPECS/college_admission_form_field_conditions.md`.
- `college_admission_form_field_scopes` — relational Degree Level/Degree/Program/Offering/Curriculum/Admission Cycle applicability. See `TABLE_SPECS/college_admission_form_field_scopes.md`.
- `college_admission_form_fields.condition_match_mode` — reserved `ALL|ANY` condition aggregation; current builder creates a first condition and backend supports deterministic aggregation.

### Stage 1 governance addition — 2026-08-27
`college_admission_form_templates.allow_college_override` explicitly governs whether an ACTIVE University form may be extended by a College. Curriculum applicability continues to reference `curricula.id`, but new selections are restricted to the current approved ACTIVE Curriculum version (no approved successor/amendment).

### Admission Seat Allocation — 2026-09-03
- `college_admission_seat_allocations` — one auditable physical seat decision per generated Merit row / Application Choice; links College, Intake bucket, optional locked Reservation Plan, Merit, Application, Choice, Score and exact Selection Rule version. ACTIVE rows consume Open or one Vertical reserved seat.
- `college_admission_seat_allocation_horizontal_categories` — zero or more Horizontal categories attached to one Seat Allocation with independent `fulfills_target` tracking. See `TABLE_SPECS/college_admission_seat_allocations.md`.

### Admission Document Verification — 2026-09-03
- `college_admission_document_verifications`: one overall verification per College Admission Application; PENDING/VERIFIED/DEFICIENT.
- `college_admission_document_verification_items`: per uploaded Admission FILE/IMAGE review; VERIFIED/REJECTED/WAIVED with reviewer/remarks.
- `college_admission_seat_allocations.college_admission_document_verification_id`: required RESTRICT FK proving the VERIFIED gate consumed by the seat decision.

## Fees, Benefits, and Payments - synchronized 2026-09-12

The following tables are implemented by migrations and have individual specs
under `DATABASE/TABLE_SPECS/`.

| Logical Entity | Physical Table | Domain | Scope / Owner | Main Parent or Link | Table Spec |
|---|---|---|---|---|---|
| Fee category | `fee_categories` | Fee Foundation | University or College | University / optional College | `TABLE_SPECS/fee_categories.md` |
| Fee head | `fee_heads` | Fee Foundation | University or College | Fee Category | `TABLE_SPECS/fee_heads.md` |
| Fee structure | `fee_structures` | Fee Foundation | University or College | Academic/program/curriculum applicability | `TABLE_SPECS/fee_structures.md` |
| Fee structure item | `fee_structure_items` | Fee Foundation | Inherits structure | Fee Structure + Fee Head | `TABLE_SPECS/fee_structure_items.md` |
| Period amount | `fee_structure_item_period_amounts` | Fee Foundation | Inherits item | Fee Structure Item | `TABLE_SPECS/fee_structure_item_period_amounts.md` |
| Period exclusion | `fee_structure_item_period_exclusions` | Fee Foundation | Inherits item | Fee Structure Item | `TABLE_SPECS/fee_structure_item_period_exclusions.md` |
| Period rule snapshot | `fee_structure_item_period_settings` | Fee Foundation | Inherits item | Fee Structure Item | `TABLE_SPECS/fee_structure_item_period_settings.md` |
| College structure adoption | `college_fee_structure_adoptions` | Fee Foundation | College | University Fee Structure | `TABLE_SPECS/college_fee_structure_adoptions.md` |
| Scholarship scheme | `fee_scholarship_schemes` | Scholarships | University or College | Academic/program applicability | `TABLE_SPECS/fee_scholarship_schemes.md` |
| Scheme category rule | `fee_scholarship_scheme_categories` | Scholarships | Inherits scheme | Scholarship Scheme + Fee Category | `TABLE_SPECS/fee_scholarship_scheme_categories.md` |
| Scheme fee-head rule | `fee_scholarship_scheme_heads` | Scholarships | Inherits scheme | Scholarship Scheme + Fee Head | `TABLE_SPECS/fee_scholarship_scheme_heads.md` |
| Student benefit | `fee_student_benefits` | Student Finance | College/admission | Admission + Fee Demand + optional Scheme | `TABLE_SPECS/fee_student_benefits.md` |
| Student benefit allocation | `fee_student_benefit_items` | Student Finance | Inherits benefit | Benefit + Demand Item | `TABLE_SPECS/fee_student_benefit_items.md` |
| Fee demand | `fee_demands` | Receivables | College/admission/session | Admission + Program Offering | `TABLE_SPECS/fee_demands.md` |
| Fee demand item | `fee_demand_items` | Receivables | Inherits demand | Demand + source Structure Item/Head | `TABLE_SPECS/fee_demand_items.md` |
| Installment schedule | `fee_installment_schedules` | Receivables | Inherits demand item | Demand + Demand Item | `TABLE_SPECS/fee_installment_schedules.md` |
| Late-fine rule | `fee_late_fine_rules` | Receivables | College/session | College + optional offering/head scope | `TABLE_SPECS/fee_late_fine_rules.md` |
| Late-fine charge | `fee_late_fine_charges` | Receivables | College/admission | Rule + Demand Item | `TABLE_SPECS/fee_late_fine_charges.md` |
| Fee payment receipt | `fee_payments` | Collections | College/admission/session | Admission | `TABLE_SPECS/fee_payments.md` |
| Payment allocation | `fee_payment_allocations` | Collections | Inherits payment | Payment + Demand Item / Late Fine | `TABLE_SPECS/fee_payment_allocations.md` |
| Gateway credential profile | `college_payment_gateways` | Online Payments | College | College | `TABLE_SPECS/college_payment_gateways.md` |
| Fee-head gateway route | `fee_head_gateway_mappings` | Online Payments | College/provider/environment | Fee Head + Gateway Profile | `TABLE_SPECS/fee_head_gateway_mappings.md` |
| Provider transaction | `online_payment_transactions` | Online Payments | College | Gateway Profile; optional Demand/Admission/Payment | `TABLE_SPECS/online_payment_transactions.md` |

`FeePaymentService` is the accounting authority for offline and verified online
posting. Online transactions may be credential tests or fee-linked payments.
Fee-linked provider success is verified before posting, linked through
`online_payment_transactions.fee_payment_id`, and protected against duplicate
posting. LIVE checkout is not enabled.

### Student Fee Ledger projection — ADR 189 (2026-09-12)
No new financial table is introduced. The Student Fee Ledger is rebuilt from `fee_demands`, `fee_demand_items`, `fee_student_benefits`, `fee_student_benefit_items`, `fee_late_fine_charges`, `fee_payments`, and `fee_payment_allocations`. Migration `2026_09_12_190000_register_student_fee_ledger_permission.php` only registers `college_fee_ledger.view` and default role grants.

## ADR 190 financial correction tables — 2026-09-12
- `fee_adjustments` — manual CREDIT/DEBIT liability corrections with immutable posting/reversal history.
- `fee_payment_refunds` — refund header tied to one original Fee Payment.
- `fee_payment_refund_allocations` — refund allocation tied to the exact original Payment Allocation and Demand accounting unit.
See `DATABASE/TABLE_SPECS/fee_adjustments.md`, `fee_payment_refunds.md`, and `fee_payment_refund_allocations.md`.

## Fee Clearance projection — ADR 197 (2026-09-16)
No new Fee Clearance domain table is introduced. Fee Clearance is an authoritative derived projection over the existing Fee domain. The projection evaluates enrollment-clearance-required Fee Demand Items against posted/active financial activity, including applicable Benefits, Payments/Allocations, Adjustments, Reversals and Refunds.

Migration `2026_09_16_080000_register_fee_clearance_permission.php` is a data/RBAC migration only. It inserts/updates `permissions.code = college_fee_clearance.view` and synchronizes default `role_permissions` grants for protected `SUPER_ADMIN` and `COLLEGE_ADMIN` roles. It creates no table, column, index, foreign key or new domain relationship.

A stored `fee_clearances` table or persisted `CLEARED` flag must not be introduced unless a later approved ADR changes ADR 197. This prevents stale clearance after financial corrections.

## Student Enrollment Foundation — ENR-0 / ADR 199 — 2026-09-16
- `students` — stable College-owned Student master; Admission/Application or IMPORT provenance; optional reused User identity.
- `student_enrollments` — Student academic membership in a session-bound College Program Offering; optional Batch/Section placement.
- `student_profile_values` — promoted dynamic Student-profile values without dynamic columns on `students`.
- `college_admission_form_fields` gains `student_data_policy` and `student_profile_key` to distinguish Application-only fields from promotable Student-profile data.
- `applicant_profiles.student_id` now has an explicit FK to `students.id`, completing the future-link contract introduced by ADR 034.

### ENR-1 Enrollment Eligibility Queue — 2026-09-16
- New domain tables/columns/FKs: **NONE**.
- Permission-data migration: `2026_09_16_153000_register_student_enrollment_view_permission.php` registers `college_student_enrollment.view` and default role grants.
- ENR-1 is a read projection over existing `admissions`, `college_program_intakes`, `college_program_offerings`, Fee Clearance/Ledger sources and `student_enrollments`.

### ENR-2 / ADR 201 database impact — 2026-09-16
No new Student-domain table or column. ENR-2 begins writing the ENR-0 foundation: `students`, `student_enrollments`, governed `student_profile_values`, and the existing `applicant_profiles.student_id` promotion link. Migration `2026_09_16_163000_register_student_enrollment_enroll_permission.php` is RBAC/data-only and registers `college_student_enrollment.enroll`; it is not schema-neutral for documentation purposes.

### ADR 202 clarification — `college_admission_form_fields` Student data policy
- No schema migration in ADR 202; ENR-0 columns are reused.
- `student_data_policy`: `APPLICATION_ONLY|STUDENT_PROFILE`. DB default remains `APPLICATION_ONLY` for backward/historical safety; the Form Builder explicitly submits `STUDENT_PROFILE` by default for newly created fields.
- `student_profile_key`: set from stable `field_key` when opted into `STUDENT_PROFILE`; cleared when returned to `APPLICATION_ONLY` (future enrollment behavior only).
- Existing `student_profile_values` are not backfilled, deleted, or rewritten when the field policy changes.

## ENR-3 / ADR 203 — Student Identity
- `students`: adds nullable `university_roll_no`; unique `(college_id, university_roll_no)`. Existing `student_uid` becomes actively generated by ENR-3.
- `student_enrollments`: adds nullable `class_roll_no`; unique `(college_program_offering_id, class_roll_no)`.
- `student_identity_settings`: one College-specific numbering configuration row.
- `student_identity_sequences`: lockable sequence state keyed by College + identity type + scope.
- No Exam Roll field/table is added; Examination owns that identifier.

### ENR-3.3 (2026-09-17)
Migration `2026_09_17_110000_add_configurable_class_roll_scope` extends existing ENR-3 infrastructure only: adds `student_identity_settings.class_roll_scope` and `student_enrollments.class_roll_scope_key`, replaces the offering-only Class Roll unique constraint with scope-aware uniqueness. No new domain table is introduced.

### ENR-4 / ADR 204 — Student Import / Migration (2026-09-17)
Migration `2026_09_17_130000_enable_student_import_migration` extends `student_enrollments` with nullable `discipline_id -> academic_disciplines.id` plus `(college_id, college_program_offering_id, discipline_id, status)` index. This is required because IMPORT provenance has no Admission/Application preference chain. Admission-origin enrollments populate the same field prospectively; historical rows use relationship fallback. Registers `college_student_import.view/manage` under Student Management. **No new domain table.**

## Student / Enrollment canonical ownership — ADR 206 (2026-09-18)

| Logical Entity | Physical Table | Domain | Scope | Primary Key | Important Business Key(s) | Main Parent/Owner |
|---|---|---|---|---|---|---|
| Student | `students` | Student | College | `id` | (`college_id`,`student_uid`); optional unique Admission/Application/User links | `colleges.id` |
| Student Enrollment | `student_enrollments` | Enrollment / Registration | College + Programme Offering | `id` | (`student_id`,`college_program_offering_id`); optional unique `admission_id` | `students.id`, `college_program_offerings.id` |
| Enrollment Course Choice | `student_enrollment_course_choices` | Enrollment / Registration | Enrollment | `id` | (`student_enrollment_id`,`curriculum_course_mapping_id`) | `student_enrollments.id` |
| Student Profile Value | `student_profile_values` | Student | Student | `id` | (`student_id`,`profile_key`) | `students.id` |

Canonical Enrollment academic columns are `student_enrollments.curriculum_id`, `discipline_id`, `specialization_id`; resolved course facts live in `student_enrollment_course_choices` with Term, Slot, Curriculum Course Mapping, Course and `selection_source`. `source_type` records provenance (`ADMISSION`/`IMPORT`) and is not a downstream academic branching key.

### ENR-6 / ADR 208 — Student Profile (2026-09-18)
No Student-domain schema change. Existing `students` and `student_profile_values` remain authoritative for permanent/core and governed dynamic profile data. `student_enrollments` and `student_enrollment_course_choices` are read-only academic context on the profile surface. Migration `2026_09_18_180000_register_student_profile_permissions.php` is RBAC/reference-data only.

## Phase 13 Course Delivery — `course_offerings` (2026-09-20)
Persistent operational delivery table introduced by ADR 209. Authority is derived from existing structures: `course_offerings.batch_id -> batches -> college_program_offerings` and `course_offerings.curriculum_course_mapping_id -> curriculum_course_mappings -> curriculum_slots -> curriculum_terms -> curricula`. The service enforces that both branches resolve to the same Program Offering Curriculum. No duplicate College/Program/Session/Curriculum/Course/Section ownership columns are stored. See `DATABASE/TABLE_SPECS/course_offerings.md`.
## Phase 13 Faculty Allocation — `faculty_allocations` (2026-09-21)

Operational Course Offering-to-Faculty bridge with optional same-Batch Section scope, teaching role, weekly load, inactive-first lifecycle and actor audit columns. See `TABLE_SPECS/faculty_allocations.md` and ADR 210.
## Phase 13 Scheduling — `college_rooms`, `timetable_entries`, `class_schedules` (2026-09-21)

Linked Room master, recurring Timetable rules and dated Class occurrences. See ADR 211 and `TABLE_SPECS/course_delivery_scheduling.md`.
# Attendance exception and eligibility — ADR 214 (2026-09-24)

- `attendance_exception_requests` — College-scoped typed Condonation / Medical-Special Exemption request and decision history, anchored to Student Enrollment, Course Offering and the resolved Academic Policy snapshot.
- `student_attendance_eligibilities` — unique Student Enrollment + Course Offering final Attendance eligibility snapshot for downstream Examination consumption.
- Migration `2026_09_24_090000_create_attendance_exception_and_eligibility.php` also registers five College-delegable RBAC permissions and grants them to protected administrator roles. No Fee table or relationship is changed.

## Internal Assessment — ADR 215 (2026-09-24)

- `internal_assessment_components` — Course Offering component definitions linked to resolved Academic Policy.
- `internal_assessment_activities` — Assignment/Quiz operational instances linked to component and Faculty Allocation.
- `internal_assessment_activity_students` — publication-time canonical Enrollment roster snapshot.
- Migration `2026_09_24_130000_create_internal_assessment_setup.php` also registers four permissions and protected-role grants.
- `internal_assessment_marks` — pre-approval ENTERED/ABSENT marks row, unique per publication-roster student, with correction revision and entry actor/time. Migration `2026_09_24_170000_create_internal_assessment_marks_entry.php` also registers three permissions and protected-role grants.
