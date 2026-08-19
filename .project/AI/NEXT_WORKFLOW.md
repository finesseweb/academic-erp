# NEXT WORKFLOW — AUTOMATIC MILESTONE EXECUTION

## Owner Command
When the project owner says:

`next`

treat it as EXPLICIT OWNER APPROVAL for exactly ONE next eligible milestone.

Do not ask the owner to repeat the milestone name when the frozen hierarchy and repository state make it unambiguous.

## Mandatory Documentation Root
Always read from:

`.project/AI/`

Required before selecting the next milestone:
1. DOCUMENTATION_ROOT.md
2. PROJECT_CONSTITUTION.md
3. MASTER_DEVELOPMENT_HIERARCHY.md
4. NEXT_WORKFLOW.md
5. CURRENT_IMPLEMENTATION_STATE.md
6. PAGE_IMPLEMENTATION_REGISTRY.md
7. DOMAIN/HIERARCHY_TO_PAGE_MAP.md
8. relevant PAGE_SPEC(s)
9. relevant SECURITY / DATABASE / THEMING / REALTIME / ADR docs

## Algorithm on `next`

### 1. Inspect Repository Reality
Inspect source code, routes, UI, API, Laravel migrations / Eloquent/schema/migrations, permissions, tests and working behavior.

Repository reality overrides stale documentation status.

### 2. Reconcile Status
If a page is already fully implemented but documented as NOT_STARTED, validate it and synchronize status.
If documented as IMPLEMENTED but incomplete, correct it to the real state.

### 3. Find the Current Position
Read MASTER_DEVELOPMENT_HIERARCHY.md from top to bottom.

### 4. Select the Earliest Eligible Incomplete Milestone
A milestone is eligible only when all required prerequisites before it are fully implemented.

If the previous milestone is partially implemented, `next` approves completing that milestone first.
Do not skip it.

### 5. Read PAGE_SPEC
Read all PAGE_SPECs related to the selected milestone.
PAGE_SPEC defines what to implement.
The frozen hierarchy defines when to implement it.

### 6. If PAGE_SPEC Is Missing
Create the PAGE_SPEC for that selected milestone first from the frozen hierarchy and existing architecture.
Then implement it in the same `next` run if no major unresolved product decision remains.

### 7. Implement Exactly One Milestone
Implement the selected milestone completely and professionally.
Include only minimum technical dependencies needed for it.
Do not implement the following milestone.

### 8. Validate
Run appropriate:
- lint
- typecheck
- tests
- build
- Laravel migrations / Eloquent/schema/migration validation
- RBAC/security tests
- responsive/theme checks for UI work

### 9. Synchronize Documentation
Update:
- relevant PAGE_SPEC status
- CURRENT_IMPLEMENTATION_STATE.md
- PAGE_IMPLEMENTATION_REGISTRY.md
- affected architecture/database/security/domain docs

### 10. Report and STOP
Report:
- selected milestone
- why it was next
- what was implemented
- validation performed
- documentation updated
- what the next eligible milestone will be

Then STOP.

A new `next` command is required before implementing another milestone.

## `next` Does NOT Mean
- implement all remaining modules
- skip prerequisites
- rebuild already completed functionality
- create fake placeholder pages
- silently change architecture
- use any documentation root other than `.project/AI/`

## Major Architecture Conflict
If the selected milestone requires a major unapproved architecture/product decision:
- remain on the same milestone;
- explain the conflict;
- ask only for that decision;
- do not jump ahead.

## Permanent Interpretation
`next` means:

"Read `.project/AI/`, inspect and reconcile repository reality, select the earliest eligible incomplete milestone from the frozen hierarchy, approve that milestone only, read its PAGE_SPEC(s), implement it completely, validate it, update documentation, report, and stop."

## Laravel + React Structure Gate

Before implementing the selected `next` milestone, read:
- `PROJECT_FOLDER_STRUCTURE.md`
- `MODULE_NAMING_STANDARD.md`
- `PAGE_FLOW_STANDARD.md`
- `REACT_FRONTEND_STANDARD.md`
- `LARAVEL_BACKEND_STANDARD.md`

The selected milestone must follow the same React/Laravel folder and page flow conventions as existing modules.

Do not create a new custom folder pattern for each page.
