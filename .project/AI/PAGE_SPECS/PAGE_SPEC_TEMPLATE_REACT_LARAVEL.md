# <PAGE NAME>

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE

## 1. Page Identity
- Module:
- Page:
- Laravel Route:
- Inertia Page:
- Ownership: University / College / Shared
- Scope:
- Page Type: List / Create / Edit / View / Workflow / Dashboard / Report

## 2. Purpose
Explain in simple business language.

## 3. Who Can Access
Required permissions:
Required scope:
Read-only conditions:

## 4. User Flow
Step-by-step flow including entry, actions, success and failure behavior.

## 5. List / Dashboard Content
Columns/widgets:
Filters:
Search:
Sorting:
Actions:
Pagination:
Bulk actions:

## 6. Create/Edit Form
Presentation: Page / Drawer / Modal
Sections:
Fields:
Validation:
Dependencies:
Unsaved-change behavior:

## 7. Business Rules
All domain rules.

## 8. Backend Contract
Laravel Controller:
Form Requests:
Service/Action:
Policy/Gate:
Inertia Props:
Optional JSON/API endpoints only if justified:
Events/Audit:

## 9. Database
Tables/models:
Relationships:
Indexes:
Uniqueness:
Historical safety:

## 10. React Files
Expected folder:
`resources/js/pages/<module>/`

Pages:
Components:
Hooks:
Types:
Validation/helpers:

## 11. Laravel Files
Expected as applicable:
- Controller
- Form Requests
- Model
- Policy
- Service/Action
- migration
- tests

## 12. Premium UI / UX Behavior — MANDATORY
Must follow `UI_UX_GUIDELINES.md` and `PAGE_FLOW_STANDARD.md`.

PageHeader:
Primary/secondary actions + icons:
Loading state / skeleton:
Button pending states:
Success message/toast:
Error message/toast:
Warning/info messages:
Validation behavior:
Empty state:
No-results state:
Error/retry state:
Confirmation dialogs:
Modal/drawer behavior:
Tooltips for icon-only controls:
Focus/keyboard behavior:
Transitions/motion:

## 13. Responsive Behavior
Desktop:
Tablet:
Mobile:

## 14. Theme Behavior
Use semantic theme tokens only. Verify all supported themes.

## 15. Realtime Decision
Standard Inertia request / JSON endpoint / WebSocket and why.

## 16. Audit
Events to audit.

## 17. Tests
Backend:
Authorization/scope:
Validation:
Inertia page/props:
UI states:
Loading/pending duplicate prevention:
Responsive/accessibility checks:
Regression:

## 18. Definition of Done
Include business correctness plus the full UI/UX Definition of Done in `UI_UX_GUIDELINES.md`.
