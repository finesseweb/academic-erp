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

### academic_policy_assessment_exam_rules
One-to-one Assessment / Examination governance configuration for `academic_policies`. Stores general pass/absence/grace/re-attempt permissions. Component-specific structures are intentionally deferred to configurable Assessment Scheme masters.

### academic_policy_grading_rules / academic_policy_grade_bands
Version-bound grading configuration with dynamic percentage-to-grade bands.

### academic_policy_progression_rules
One-to-one, version-bound Academic Policy configuration for promotion/progression thresholds and controlled carry-forward/detention/year-back/re-admission permissions.

### academic_policy_progression_rule_sets / academic_policy_progression_rule_terms

Dynamic version-bound progression checkpoints. Multiple source Curriculum Terms can be mapped to one target Term. This replaces legacy `academic_policy_progression_rules`.
