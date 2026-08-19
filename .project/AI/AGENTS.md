# AI Agent Rules

Always read `PROJECT_CONSTITUTION.md` first, then follow `README.md`.

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

## Mandatory Documentation and Approval Gate
Before any project work, read `.project/AI/` and treat it as authoritative.

Do not implement planned pages automatically.
Only implement the page/milestone explicitly approved by the project owner.
Do not continue to the next page after completion without new approval.
Keep affected `.project/AI/` documentation synchronized with approved implementation.

## Automatic `next` Workflow — Mandatory

If the owner says `next`, do not ask what to build.

Read `.project/AI/MASTER_DEVELOPMENT_HIERARCHY.md`,
`.project/AI/NEXT_WORKFLOW.md`,
`.project/AI/CURRENT_IMPLEMENTATION_STATE.md`,
and the relevant PAGE_SPEC(s).

Inspect the repository, select the earliest eligible incomplete milestone, implement it, validate it, synchronize documentation, report and stop.

`next` approves exactly one milestone.

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
