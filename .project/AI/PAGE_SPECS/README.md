# Page Specs

Every implemented page must have a PAGE_SPEC and every PAGE_SPEC must identify permissions, scope, API, data model, realtime decision, audit behavior and theme compatibility.

Start platform implementation with:
`PAGE_SPECS/UNIVERSITY_ADMIN/README.md`

Use `PAGE_SPEC_TEMPLATE.md` for new pages.
Use `PROMPTS/PAGE_CREATION_PROMPT.md` to instruct Cursor/AI to implement one page safely.

Do not create a database table merely because a page exists. Design entities by domain and reuse existing new-ERP entities where they already represent the concept.

## University and College Domain
All PAGE_SPECs must respect the canonical hierarchy in `../DOMAIN/UNIVERSITY_HIERARCHY.md`.

Financial PAGE_SPECs must respect `../DOMAIN/FEE_GOVERNANCE_AND_INSTALLMENTS.md`, including University-vs-College control and College-level Installment Plans.

## Hierarchy-Driven Page Modules

The canonical University hierarchy now maps to concrete PAGE_SPEC modules:

- `UNIVERSITY_ADMIN/` - University administration, affiliated Colleges, users, roles, security, themes.
- `UNIVERSITY_FINANCE/` - University fee governance and consolidated finance.
- `COLLEGE_FINANCE/` - College-level fee structures, installments, collections, dues, adjustments and reports.

When a new hierarchy branch is approved, create its PAGE_SPEC module rather than placing unrelated pages in a generic folder.

### Finance Rule
College Fee Management must include Installment Plans as a first-class page/capability. Installment plans schedule approved payable fees; they do not alter University-fixed fee amounts.

