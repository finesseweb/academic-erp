# ADR 159 — Late Fine UI Project Layout Consistency

## Decision
The Late Fine / Penalty page must use the same page-shell convention as existing Fee Management pages. It must not mount `AppLayout` inside the page because the application shell is already provided by the project.

## Rules
- No nested `AppLayout` on the Late Fine page.
- No external CSS or independent styling layer is introduced.
- Reuse existing project UI components (`Card`, `Button`, `Input`, `Badge`, project `DatePicker`) and the same compact page spacing used by Fee Demand / Student Benefits.
- The change is UI-shell only; Late Fine business rules, RBAC, calculations, audit history, and Test Data Cleanup behavior remain unchanged.

## Reason
The nested layout duplicated the application shell/sidebar/header and visually disturbed the existing ERP design.
