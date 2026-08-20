# University ERP AI Documentation

## Always Start Here
The authoritative project documentation root is exactly `.project/AI/`.

Every AI/Codex session must read this documentation before implementation. Never assume `/AI` or another path.

## Core Business Model
This software is a **University ERP**.

- **University** is the top-level business/domain entity.
- One University may have multiple **Affiliated Colleges**.
- `SUPER_ADMIN` is the highest University-level system role. It is not the top-level business entity.
- Each Affiliated College may contain administration, campuses, faculties/schools, departments, programs, batches, terms, courses, employees/faculty, students, parents/guardians and College Fee Management.
- University finance governance and College fee operations are layered. College-level installment plans are supported.

## Read Order
1. `DOCUMENTATION_ROOT.md`
2. `PROJECT_CONSTITUTION.md`
3. `DOMAIN/UNIVERSITY_HIERARCHY.md`
4. `DOMAIN/FEE_GOVERNANCE_AND_INSTALLMENTS.md`
5. `ARCHITECTURE.md`
6. `DATABASE_RULES.md`
7. `SECURITY/RBAC_DESIGN.md`
8. `SECURITY/PERMISSION_CATALOG.md`
9. `THEMING/THEME_SYSTEM.md`
10. Relevant `PAGE_SPECS/` file

## University Administration Page Specs
Start platform administration from `PAGE_SPECS/UNIVERSITY_ADMIN/README.md`.

The University Administration experience includes:
- University Profile
- Affiliated Colleges
- University Dashboard
- Users
- Roles & Permissions
- Audit Logs
- Themes / System settings

Use `PAGE_SPECS/PAGE_SPEC_TEMPLATE.md` for new pages.

## Documentation Maintenance
Whenever functionality changes, update the appropriate authoritative files under `.project/AI/`. Documentation synchronization is part of the Definition of Done.

## Hierarchy-to-Page Mapping
Before creating University, College, or Finance pages, read:
- `DOMAIN/UNIVERSITY_HIERARCHY.md`
- `DOMAIN/HIERARCHY_TO_PAGE_MAP.md`
- `DOMAIN/FEE_GOVERNANCE_AND_INSTALLMENTS.md`

The hierarchy must be reflected in PAGE_SPECs, navigation, RBAC scope, database ownership and reporting.

## Start Here — Mandatory Workflow
For every new Codex/AI session:
1. Read `.project/AI/DOCUMENTATION_ROOT.md`.
2. Read `.project/AI/PROJECT_CONSTITUTION.md`.
3. Read the University hierarchy and hierarchy-to-page map.
4. Read `IMPLEMENTATION_APPROVAL_POLICY.md`.
5. Read only the relevant PAGE_SPEC and supporting domain/security/database/theme/realtime docs.
6. Implement only the owner-approved milestone or owner-approved `next` batch count.
7. Validate and update affected docs after each milestone.
8. Stop when the approved count is complete and wait for a new owner command.

## `next` / `next N` Development Workflow

The owner can drive development with:
- `next` for 1 next eligible incomplete milestone.
- `next 2` or `next2` for 2.
- `next N` or `nextN` for any positive integer N.

Codex/AI first inspects the existing repository and reconciles implementation-status documentation, then resumes at the earliest genuinely incomplete eligible milestone in the frozen hierarchy. Already-completed milestones are skipped and do not consume the requested count. It completes, validates and documents each milestone before moving to the next within the same approved batch, then stops after exactly the requested number.

Replacing these workflow files does not reset project progress.

Key files:
- `MASTER_DEVELOPMENT_HIERARCHY.md`
- `NEXT_WORKFLOW.md`
- `CURRENT_IMPLEMENTATION_STATE.md`
- `PAGE_IMPLEMENTATION_REGISTRY.md`

## React + Laravel + MySQL Project Standard

Frozen technology:
- React + TypeScript + Vite
- Laravel REST API
- MySQL

Start with:
1. `DOCUMENTATION_ROOT.md`
2. `DEVELOPER_NAVIGATION_INDEX.md`
3. `ARCHITECTURE.md`
4. `PROJECT_FOLDER_STRUCTURE.md`
5. `PAGE_FLOW_STANDARD.md`
6. `MASTER_DEVELOPMENT_HIERARCHY.md`
7. `NEXT_WORKFLOW.md`

Every page must use the same documented flow and folder conventions.

## Mandatory Premium UI / UX Standard
All UI implementation must read and comply with `UI_UX_GUIDELINES.md` before coding.
This includes consistent icons, page/navigation loaders, skeleton states, pending controls, toasts/alerts, validation feedback, informative messages, empty/no-result/error states, confirmation dialogs, responsive behavior and accessibility.
For standard internal ERP screens the frozen stack is Laravel 13 + React 19 + TypeScript + Inertia + Vite + MySQL in one Laravel application.
