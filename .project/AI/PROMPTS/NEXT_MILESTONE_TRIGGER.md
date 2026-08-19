# NEXT MILESTONE TRIGGER

When the owner says `next`:

1. Read all authoritative documentation from `.project/AI/`.
2. Inspect repository reality.
3. Reconcile CURRENT_IMPLEMENTATION_STATE.md and PAGE_SPEC statuses.
4. Read MASTER_DEVELOPMENT_HIERARCHY.md from top to bottom.
5. Select the earliest incomplete milestone whose prerequisites are implemented.
6. Treat `next` as explicit owner approval for that milestone only.
7. Read all related PAGE_SPEC(s) and supporting SECURITY/DATABASE/THEMING/REALTIME/ADR docs.
8. Implement the milestone completely.
9. Preserve existing working functionality.
10. Run all required validation.
11. Synchronize `.project/AI/`.
12. Report completion and name the next eligible milestone.
13. STOP.

Do not ask which milestone is next unless a genuine unresolved architecture decision prevents implementation.
Do not implement a second milestone until the owner says `next` again.

## Frozen React + Laravel + MySQL Stack

Frontend:
React + TypeScript + Vite

Backend:
Laravel REST API

Database:
MySQL

Before code generation read:
- `.project/AI/PROJECT_FOLDER_STRUCTURE.md`
- `.project/AI/MODULE_NAMING_STANDARD.md`
- `.project/AI/PAGE_FLOW_STANDARD.md`
- `.project/AI/REACT_FRONTEND_STANDARD.md`
- `.project/AI/LARAVEL_BACKEND_STANDARD.md`

Do not generate Next.js, NestJS or Prisma code.
Do not create a new folder convention per page.
