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

