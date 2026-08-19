# Codex Master Implementation Prompt

Use this at the beginning of an implementation session.

```text
You are implementing a production-grade Academic ERP using React, Laravel, Laravel migrations / Eloquent, MySQL, and Socket.IO/WebSocket only where realtime behavior is justified.

BEFORE ANY CODE CHANGE:
1. Read the complete .project/AI/ documentation directory, prioritizing PROJECT_CONSTITUTION.md, AGENTS.md, ARCHITECTURE.md, DATABASE_RULES.md, DOCUMENTATION_MAINTENANCE.md, relevant SECURITY docs, THEME_SYSTEM.md, UI_UX_GUIDELINES.md and the relevant PAGE_SPEC.
2. Inspect existing source code and reuse established components/patterns.
3. Identify the first incomplete approved milestone and implement only that milestone completely.

QUALITY:
- Premium modern enterprise SaaS/ERP UI; restrained, professional, consistent and responsive.
- Maintain one AppShell, sidebar/header/page-header system, consistent spacing/alignment and reusable components.
- No generic Bootstrap appearance, random spacing, excessive gradients/shadows, duplicated components, arbitrary hard-coded theme colors or misaligned forms/tables.
- Verify desktop/laptop/tablet/mobile composition, loading/empty/error states, keyboard/focus accessibility and visual polish.

THEMES:
Support Premium Light, Premium Dark, Ocean Blue and Emerald through shared design tokens. Components must also work with validated custom themes. Never permit executable custom CSS/JS as theme input.

SECURITY:
Laravel is the authorization authority. Enforce Authentication -> Role -> Permission -> Scope -> Resource/business rules. Frontend permission visibility is UX only and never replaces backend enforcement.

BACKEND:
Controller -> Service -> Repository -> Laravel migrations / Eloquent -> MySQL. Use DTO validation, transactions where needed, proper constraints/indexes and named Laravel migrations / Eloquent migrations. This is a brand-new database designed professionally by domain need.

REALTIME:
Use REST for normal CRUD. Use Socket.IO only for genuine realtime needs and enforce authentication/authorization on socket connections and rooms.

MANDATORY DOCUMENTATION:
Follow .project/AI/DOCUMENTATION_MAINTENANCE.md. Every new or changed page, module, table, relation, permission, role, API, realtime event, theme, workflow or business rule must update its respective authoritative documentation. Do not create random docs when an existing document owns the concept. Major architecture/product changes require approval before implementation and an ADR. Normal implementation changes should be implemented and documented automatically.

DEFINITION OF DONE:
Run lint, typecheck, relevant tests, migration validation when applicable, responsive/theme review when applicable, and permission/scope checks. Report IMPLEMENTATION, DATABASE, SECURITY, DOCUMENTATION UPDATED, and VALIDATION. Do not mark work complete while documentation is stale.

Start by inspecting the repository and .project/AI/ documentation. Briefly report what exists, what is missing, what you will implement in this iteration, and affected files/database/security/docs; then implement the first incomplete approved milestone.
```

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
