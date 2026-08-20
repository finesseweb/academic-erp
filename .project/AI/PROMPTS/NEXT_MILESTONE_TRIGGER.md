# NEXT MILESTONE TRIGGER

**Command parsing precedence:** Never reinterpret `next N` as plain `next`. If N is supplied, the full N-milestone batch is approved in this run.

## Accepted Owner Commands
- `next` = implement 1 next eligible incomplete milestone.
- `next N` or `nextN` = implement exactly N next eligible incomplete milestones, where N is a positive integer.

Examples: `next`, `next 2`, `next2`, `next 5`, `next5`.

## Mandatory Resume Behavior
For every accepted command:
1. Read all authoritative documentation from `.project/AI/`.
2. Inspect repository reality before choosing work.
3. Reconcile `CURRENT_IMPLEMENTATION_STATE.md`, `PAGE_IMPLEMENTATION_REGISTRY.md` and PAGE_SPEC statuses with actual code.
4. Read `MASTER_DEVELOPMENT_HIERARCHY.md` from top to bottom.
5. Resume at the earliest genuinely incomplete milestone whose prerequisites are implemented.
6. Skip milestones already complete in repository reality; they do not consume the requested count.
7. If the earliest eligible milestone is partially implemented, finish it first and count it as one.
8. Never reset to the start simply because these instruction files were replaced.

## Batch Execution
Treat the command as explicit owner approval for exactly the requested number of eligible incomplete milestones.

For each milestone in order:
1. Read all related PAGE_SPEC(s) and supporting SECURITY/DATABASE/THEMING/REALTIME/ADR docs.
2. Implement the milestone completely.
3. Preserve existing working functionality.
4. Run required validation.
5. Synchronize `.project/AI/`.
6. Only after completion + validation + documentation, select the next eligible incomplete milestone if the requested batch count has not yet been reached.

After exactly the requested number of newly completed milestones, report completion, name the next eligible incomplete milestone, and STOP.

If a genuine unresolved architecture/product decision blocks a selected milestone, stop on that milestone and do not skip ahead.

## Frozen React + Laravel + MySQL Stack
Frontend: React + TypeScript + Vite
Backend: Laravel REST API / established Laravel project flow
Database: MySQL

Before code generation read:
- `.project/AI/PROJECT_FOLDER_STRUCTURE.md`
- `.project/AI/MODULE_NAMING_STANDARD.md`
- `.project/AI/PAGE_FLOW_STANDARD.md`
- `.project/AI/REACT_FRONTEND_STANDARD.md`
- `.project/AI/LARAVEL_BACKEND_STANDARD.md`

Do not generate Next.js, NestJS or Prisma code. Do not create a new folder convention per page.
