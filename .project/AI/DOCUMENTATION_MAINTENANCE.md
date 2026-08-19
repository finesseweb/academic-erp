# Documentation Ownership and Automatic Maintenance

## Status
MANDATORY PROJECT RULE

The ``.project/AI/` directory is the authoritative living technical knowledge base for this ERP. Code, database schema, security behavior, UI specifications, realtime behavior, and documentation must remain synchronized.

## Mandatory rule
Whenever any functionality, page, module, database structure, relationship, permission, role, API, WebSocket event, theme capability, workflow, business rule, or architectural behavior is introduced or changed, the implementing AI/developer MUST update the respective documentation before the task is considered complete.

Documentation updates are part of the Definition of Done. Never leave documentation stale after implementation.

## Before implementation
1. Identify the existing documents that own the affected concepts.
2. Read those documents before changing code.
3. Read the relevant PAGE_SPEC for UI/page work.
4. Check whether the request conflicts with the documented architecture or constitution.
5. Normal implementation decisions may proceed when consistent with project rules.
6. Major architecture/product changes must not be made silently. Explain the conflict/change and obtain approval before implementation.

## Documentation routing

### Page / UI
- Existing page changed -> update `PAGE_SPECS/<MODULE>/<PAGE>.md`.
- New page -> create `PAGE_SPECS/<MODULE>/<NEW_PAGE>.md` using `PAGE_SPEC_TEMPLATE.md`.
- New module -> create its directory under `PAGE_SPECS/` and document each page.
- Shared design-system/layout behavior -> update `UI_UX_GUIDELINES.md` and/or theming documentation.

### Database
- New table -> update `DATABASE/SCHEMA_CATALOG.md` and create `DATABASE/TABLE_SPECS/<TABLE>.md`.
- Table changed -> update its TABLE_SPEC and catalog where applicable.
- New/changed relationship -> update `DATABASE/RELATIONSHIP_MAP.md` and affected TABLE_SPECS.
- Column/index/constraint -> update affected TABLE_SPEC; update catalog/indexing documentation when relevant.
- Schema change must be represented by Laravel migrations / Eloquent and a named migration.

### Security
- New/changed permission -> `SECURITY/PERMISSION_CATALOG.md`.
- Role/default role behavior -> `SECURITY/ROLE_MATRIX.md`.
- RBAC architecture -> `SECURITY/RBAC_DESIGN.md`.
- Authorization/scope behavior -> `SECURITY/AUTHORIZATION_FLOW.md`.
- Audit behavior/events -> `SECURITY/AUDIT_LOGGING.md`.

### API / Realtime
- REST API behavior -> relevant PAGE_SPEC/module documentation and central API docs if present.
- WebSocket event, room, namespace, authentication or realtime behavior -> `REALTIME/WEBSOCKET_ARCHITECTURE.md`.

### Themes
- Theme tokens, built-in themes, custom-theme behavior or theme security -> `THEMING/THEME_SYSTEM.md`.
- Theme-management page behavior -> relevant PAGE_SPEC.

### Architecture
- Project-wide architecture change -> `ARCHITECTURE.md` and an ADR under `DECISIONS/`.
- Fundamental project rule -> `PROJECT_CONSTITUTION.md` only when truly necessary.
- Delivery/migration strategy -> appropriate roadmap/migration documentation.

## Do not create documentation randomly
Always locate and update the authoritative existing document first. Create a new document only when a genuinely new page/module/domain exists, no current document appropriately owns the information, or the documentation structure explicitly requires a separate document.

Avoid duplicating detailed rules across many files. Keep one authoritative source and reference it from related documents when appropriate.

## Normal change vs major change
Normal implementation change:
- design the implementation professionally within the approved architecture;
- implement it;
- test it;
- automatically update all respective documentation.

Major architecture/product change (examples: changing primary database technology, replacing REST architecture, replacing RBAC model, changing tenant model):
- stop before silently implementing it;
- explain the proposed change and impact;
- obtain approval;
- implement after approval;
- update architecture/ADR and all affected documentation.

## Definition of Done
A task is NOT complete if any of the following is true:
- code works but PAGE_SPEC is stale;
- Laravel migrations / Eloquent/schema changed but database documentation is stale;
- permission changed but PERMISSION_CATALOG is stale;
- role behavior changed but role/RBAC documentation is stale;
- WebSocket behavior changed but realtime documentation is stale;
- theme behavior changed but theme documentation is stale;
- architecture changed without an ADR/documentation update;
- frontend hides an action but backend authorization is missing.

At the end of every implementation task report:

### IMPLEMENTATION
- Created/modified functionality and files.

### DATABASE
- Tables, relations, migrations, constraints or indexes affected; or `No database change`.

### SECURITY
- Permissions, roles, scopes, audit changes; or `No security change`.

### DOCUMENTATION UPDATED
- List each documentation file changed and why.
- If none were required, explicitly state `Documentation update not required` and explain why.

### VALIDATION
- Lint
- Typecheck
- Relevant tests
- Laravel migrations / Eloquent/migration validation where applicable
- Responsive UI check where applicable
- All four built-in themes + custom-theme compatibility where applicable
- Permission/scope enforcement where applicable

Never claim completion until implementation, database, security, tests and documentation are synchronized.
