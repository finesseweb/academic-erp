# DOCUMENTATION ROOT — MANDATORY

## Canonical Path
`.project/AI/` is the single authoritative project documentation root.

Every AI agent, Codex session, developer workflow, implementation prompt, review, migration, refactor, page creation, database change, security change, theme change, and architecture decision MUST read from `.project/AI/` before making project changes.

Do not treat any of these as authoritative:
- `/AI`
- `AI/`
- `.projects/AI/`
- `.projects/Ai/`
- `.project/Ai/`
- copied or temporary documentation elsewhere

## Required Reading Order
Before implementation:
1. `.project/AI/DOCUMENTATION_ROOT.md`
2. `.project/AI/PROJECT_CONSTITUTION.md`
3. `.project/AI/ARCHITECTURE.md`
4. `.project/AI/DOMAIN/UNIVERSITY_HIERARCHY.md`
5. `.project/AI/DOMAIN/HIERARCHY_TO_PAGE_MAP.md`
6. relevant `.project/AI/PAGE_SPECS/...`
7. relevant SECURITY / DATABASE / THEMING / REALTIME documents
8. relevant ADRs and implementation roadmap

## Synchronization Rule
Code and documentation must remain synchronized. When approved implementation changes behavior, update the affected documents under `.project/AI/`.

## Approval Boundary
Documentation is NOT implementation approval.
A documented future page/module must not be implemented until the project owner explicitly approves that page or milestone.

## Laravel + React Required Architecture Reading

For code implementation also read:
- `ARCHITECTURE_LARAVEL_REACT.md`
- `PROJECT_FOLDER_STRUCTURE.md`
- `MODULE_NAMING_STANDARD.md`
- `PAGE_FLOW_STANDARD.md`
- `REACT_FRONTEND_STANDARD.md`
- `LARAVEL_BACKEND_STANDARD.md`
