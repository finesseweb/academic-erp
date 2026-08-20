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
