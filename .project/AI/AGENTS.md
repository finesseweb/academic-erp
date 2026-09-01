# AI Agent Rules

Always read `PROJECT_CONSTITUTION.md` first, then follow `README.md`.


## Project Context Access and Consistency Rule — Mandatory
Before developing or changing anything, determine whether the agent has sufficient access to the current project and its authoritative references.

### Agent with full project access
If the agent (for example Codex or another repository-aware agent) has access to the complete current project/repository and `.project/AI/` documentation, it MUST inspect the relevant existing implementation and authoritative documents first, then develop consistently from project reality. It should not ask the owner for references that are already available in the repository.

The inspection must include every affected layer as applicable: UI/design patterns, components, routes, controllers/services, models, migrations/schema, relationships, permissions/RBAC/scope, naming conventions, workflows, audit behavior, tests, and living documentation. Repository reality plus `.project/AI/` are the development reference.

### Agent with partial or no project-file access
If the agent does NOT have full access to the relevant current project files, it MUST NOT guess existing architecture or invent a parallel pattern. Whenever an existing reference is needed to preserve consistency, it must ask the owner for the specific relevant file(s), code, screenshot, schema, PAGE_SPEC, or other project reference before finalizing that part of the implementation.

This applies especially to:
- UI layout, design system, components, forms, tables, navigation and interaction patterns;
- database tables, columns, constraints, indexes, relationships and migrations;
- Laravel controllers/services/policies/models and backend conventions;
- React/Inertia page/component structure and frontend conventions;
- routes, route names and URL hierarchy;
- permissions, roles, scopes, audit behavior and security rules;
- module names, terminology, status values and workflow/lifecycle rules;
- documentation structure and implementation-state updates.

The goal is one consistent ERP. Lack of file access is never permission to guess. Ask only for the missing reference actually needed, then continue according to the established project.

## Before Changing Any Feature
- Read its PAGE_SPEC/feature documentation.
- Inspect the current Laravel migrations / Eloquent schema and relevant new-ERP table specs/relationships.
- Identify business entities, permissions, scope, API behavior and whether realtime behavior is actually needed.
- When legacy functionality is referenced, use Zend/MySQL only to understand business behavior; never treat its schema as the target design.
- Explain the planned change, make a safe modular change, and update documentation when behavior/schema/security changes.

## Database Rule
The database is brand new. Design it professionally for the new ERP.
Reuse new-ERP entities that already represent the concept; create/modify schema when the domain genuinely requires it. Never create duplicate entities merely for a new page.
Every schema change must update Laravel migrations / Eloquent/migrations plus schema catalog, relationship map, relevant table specs and PAGE_SPEC.

## Realtime Rule
Do not use WebSocket merely because it is available. First classify the interaction as REST or realtime. If realtime is justified, follow `REALTIME/WEBSOCKET_ARCHITECTURE.md` and enforce authentication, RBAC and scope on socket connection, room membership and events.

## Safety
Never rewrite the whole project without need. Never make uncontrolled production changes. Destructive/data-loss migrations require explicit approval.

## Authorization / RBAC Rule
Before implementing a protected page, endpoint or socket event, read the security documents, identify exact permission codes and scope, update the permission catalog when needed, reference permissions in PAGE_SPEC, enforce authorization in Laravel, and test allowed/denied/cross-tenant access.

## Theme Rule
Before implementing or changing UI, read `THEMING/THEME_SYSTEM.md`. Every page/component must use semantic tokens and remain compatible with Premium Light, Premium Dark, Ocean Blue, Emerald and validated custom themes. Never accept arbitrary raw CSS/JS as a custom theme.

## Mandatory Living Documentation Rule
Every agent MUST follow `DOCUMENTATION_MAINTENANCE.md`. Any implementation that introduces or changes a page, module, database object, relationship, permission, role, API, realtime event, theme, workflow, or business rule must update its authoritative documentation before completion. Major architecture/product changes require approval before implementation; normal implementation changes should update documentation automatically.

## Documentation Location

The canonical documentation root is `.project/AI/`. Read `DOCUMENTATION_ROOT.md` before resolving any documentation path. Do not assume `/AI` or `AI/` exists elsewhere in the repository.

Do **not** create a parallel root-level `docs/` folder for implementation notes, ADRs, patch readmes, architecture decisions, or living project documentation. All such project documentation must be merged into the appropriate authoritative file under `.project/AI/` (for example `DECISIONS/`, `CURRENT_IMPLEMENTATION_STATE.md`, `CHANGELOG.md`, database docs, or the relevant PAGE_SPEC). A patch is not documentation-complete until `.project/AI/` is synchronized.

## Mandatory Documentation and Approval Gate
Before any project work, read `.project/AI/` and treat it as authoritative.

Do not implement planned pages automatically.
Only implement the page/milestone explicitly approved by the project owner.
Do not continue beyond the owner-approved count. For `next N`/`nextN`, the N milestones are already explicitly approved as one sequential batch.
Keep affected `.project/AI/` documentation synchronized with approved implementation.

## Automatic `next` / `next N` Workflow — Mandatory

If the owner says `next`, `next N`, or `nextN`, do not ask what to build when the hierarchy is unambiguous. Read the master hierarchy, workflow, current implementation state, registry, PAGE_SPECs and required supporting docs.

Inspect repository reality first and resume from the earliest genuinely incomplete eligible milestone. Repository reality overrides stale status docs. Already-completed milestones are skipped and do not consume the requested count; a partially completed earliest eligible milestone is finished first and counts as one.

`next` approves exactly 1 newly completed milestone. `next N`/`nextN` approves exactly N newly completed milestones. Complete, validate and document each milestone before proceeding to the next in that approved batch. Stop after exactly the requested count. Replacing these workflow files never resets progress.
## Frozen React + Laravel + MySQL Stack

Frontend:
React 19 + TypeScript + Inertia + Vite

Backend:
Laravel 13 controllers/services/policies with Inertia for normal ERP pages

Database:
MySQL

Before code generation read:
- `.project/AI/PROJECT_FOLDER_STRUCTURE.md`
- `.project/AI/MODULE_NAMING_STANDARD.md`
- `.project/AI/PAGE_FLOW_STANDARD.md`
- `.project/AI/REACT_FRONTEND_STANDARD.md`
- `.project/AI/UI_UX_GUIDELINES.md`
- `.project/AI/LARAVEL_BACKEND_STANDARD.md`

Do not generate Next.js, NestJS or Prisma code.
Do not create a new folder convention per page.


## Premium UI / UX Quality Gate — Mandatory
Before implementing or changing any UI, read `.project/AI/UI_UX_GUIDELINES.md`.
A page is incomplete if it lacks appropriate loaders/skeletons, pending button states, professional semantic messages/toasts, validation feedback, empty/no-result/error states, meaningful icons, destructive confirmations, responsive behavior, accessibility/focus behavior, and permission-aware actions.
Do not create visually inconsistent one-off CRUD screens. Reuse the shared application shell and design-system components.

## Common Approval Engine Rule — Mandatory
When a current or future module requires approval, first inspect and reuse the existing Academic Approval engine. Do not create a separate module-specific approval system. Keep workflow/stage/role/decision/history mechanics common, while keeping subject-specific validation and final lifecycle behavior in the module's approval handler/service.

Every approval submission must enforce in Laravel that the workflow is ACTIVE, belongs to the same University/scope, and has an `applies_to` value matching the subject type. Never rely only on a frontend dropdown/filter for this constraint.
