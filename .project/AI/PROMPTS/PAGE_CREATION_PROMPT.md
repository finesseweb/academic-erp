# Master Prompt — Create One ERP Page

Use this prompt with Cursor/AI when implementing a page. Replace the bracketed values and attach/reference the page spec.

```text
You are implementing ONE page in the College ERP.

Project stack:
- Frontend: React
- Backend: Laravel
- ORM: Laravel migrations / Eloquent
- Database: MySQL
- Realtime: Socket.IO only when the PAGE_SPEC explicitly justifies it
- Authorization: granular RBAC + explicit scope

Mandatory reading before coding:
1. .project/AI/PROJECT_CONSTITUTION.md
2. .project/AI/AGENTS.md
3. .project/AI/ARCHITECTURE.md
4. .project/AI/CODING_STANDARDS.md
5. .project/AI/API_STANDARDS.md
6. .project/AI/UI_UX_GUIDELINES.md
7. .project/AI/SECURITY_RULES.md
8. .project/AI/SECURITY/RBAC_DESIGN.md
9. .project/AI/SECURITY/PERMISSION_CATALOG.md
10. .project/AI/THEMING/THEME_SYSTEM.md
11. .project/AI/PAGE_SPECS/[PAGE_SPEC_FILE]
12. Relevant database/table documentation

Task:
Implement [PAGE NAME] exactly according to its PAGE_SPEC.

Rules:
- Do not implement unrelated modules.
- Reuse existing shared components and domain entities before adding new ones.
- If schema changes are genuinely required, update Laravel migrations / Eloquent schema, create a named migration, and update SCHEMA_CATALOG, RELATIONSHIP_MAP and TABLE_SPECS.
- Laravel is the authorization authority. Hiding buttons in React is not security.
- Enforce every permission and scope listed in the PAGE_SPEC.
- Use REST unless the PAGE_SPEC explicitly requires WebSocket.
- Do not put Laravel migrations / Eloquent calls directly in controllers.
- Keep controller -> service -> repository -> Laravel migrations / Eloquent boundaries.
- Use DTO validation and consistent API error handling.
- Add audit logging required by the PAGE_SPEC.
- Use server-side pagination/filtering/sorting for administrative lists where specified.
- The page must work with Premium Light, Premium Dark, Ocean Blue, Emerald and validated custom themes.
- Use semantic theme tokens; do not hard-code theme-specific colors inside the page.
- Make the UI responsive, accessible, premium and consistent with shared ERP patterns.
- Add loading, empty, error and disabled states.
- Add tests for success, validation, permission denial and scope denial where applicable.

Before changing code:
1. Summarize the PAGE_SPEC requirements.
2. List existing files/components/entities you will reuse.
3. List files you intend to create/change.
4. State whether a DB migration is required and why.
5. State whether WebSocket is required and why.

Then implement the smallest complete vertical slice.

After implementation:
- list created/changed files;
- list APIs created/changed;
- list permissions enforced;
- list migrations if any;
- list tests added;
- report any PAGE_SPEC/documentation updates.
```
