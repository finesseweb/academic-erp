# University Administration Implementation Roadmap

## Domain Rule
The ERP is University-centered. The University is the business root. `SUPER_ADMIN` is the highest University-level role and operates the University Administration workspace.

## Module 1 — Authentication
Pages: Login, Forgot Password, Reset Password, Change Password, Profile.

## Module 2 — University Administration Shell & Dashboard
Protected `/admin` workspace with sidebar, topbar, page header, responsive navigation, themes and permission-aware menu presentation.

Dashboard should eventually show real University-level summaries such as affiliated Colleges, users, roles and security activity.

## Module 3 — Role & Permission Management
Roles List, Create Role, Edit Role, Permission Matrix and Permission Catalog.
RBAC supports University-wide, College and narrower scopes.

## Module 4 — User Management
Users List, Create/Edit User and Role/Scope Assignment.

## Module 5 — University & Affiliated College Management
Pages:
- University Profile
- Affiliated Colleges List
- Create Affiliated College
- Edit Affiliated College

Each College can later contain Administration, Campus, Faculties/Schools, Departments, Programs, Batches, Semesters/Terms, Courses, Employees/Faculty, Students, Parents/Guardians and College Fee Management.

College Fee Management includes Fee Structure and College-level Installment Plans under University finance policy.

## Module 6 — Audit & Themes
Audit Logs and Theme Management.
Theme priority: permitted User Preference -> Affiliated College Default -> University Global Default -> Premium Light fallback.

## Acceptance Direction
The University administration foundation is complete only when authentication, RBAC enforcement, role management, user scope assignment, University/Affiliated College management, audit and documented theme controls operate consistently.


## Curriculum Structure Update — 2026-08-22
- Curriculum Header: implemented.
- Terms / Semesters: implemented.
- Curriculum Slots Phase 1: implemented with Course Category, Slot Name and Display Order.
- Slot-level Credits are not introduced.
- Course / Paper Mapping remains a later milestone.
- Copy / Clone Structure is approved as the future reuse pattern when a new Curriculum/version needs an existing structure; source and target records remain independent.
