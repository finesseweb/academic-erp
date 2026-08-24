# PREMIUM UI / UX / INTERACTION STANDARD — MANDATORY

Status: FROZEN BASELINE
Applies to: Every authenticated and public page in the Academic ERP
Stack: Laravel 13 + React 19 + TypeScript + Inertia + Vite + MySQL

## 1. Product Experience Goal
The ERP must feel like a polished commercial SaaS product, not a collection of CRUD screens. Every page must be clear, responsive, accessible, consistent, fast-feeling, and predictable.

## 2. Global Application Shell
Authenticated pages must use one reusable application shell with:
- responsive collapsible sidebar
- module-aware navigation groups
- active route state
- top header
- breadcrumb trail
- page title and contextual subtitle
- global search placeholder/command area
- notifications entry
- user/profile menu
- institution/campus/scope indicator where relevant
- responsive main content region

## 3. Visual Consistency
Use shared design tokens for spacing, typography, colors, borders, radii, shadows, elevation, transitions and focus rings.
Never hard-code one-off visual styles when a reusable token/component can be used.
All supported themes must preserve contrast, hierarchy and state visibility.

### Shared Institution Branding Standard
- Generic framework branding such as the Laravel name or logo must not appear in the production ERP shell.
- Institution branding must come from one reusable server-resolved branding context and one shared React presentation component; pages must not independently resolve, upload, resize or style institution logos.
- An authenticated University-scoped user sees the University logo and University name. An authenticated College-scoped user sees the effective affiliated College logo and College name.
- When horizontal space is limited, the UI may show the institution code beside the logo; the full institution name must remain available through accessible text or a tooltip.
- If an institution has no usable logo, use the shared University/College building icon fallback with the institution name or code. Never fall back to Laravel branding.
- The active institution logo must be reused wherever institutional identity is required, including the application header/sidebar, profile/scope indicators, approved dashboards, generated documents/reports, receipts, and print/export templates. Usage on legally controlled documents remains subject to that document's approval and snapshot rules.
- Before authentication establishes an effective user scope, the central login experience uses University/application branding. College branding on a dedicated College URL or domain is permitted only when that College context has been securely resolved by the backend.
- University logo upload is managed from University Profile > Branding. College logo upload is managed from Affiliated College > Branding. Upload controls must use the shared file-upload experience with preview, progress/loading, validation, replace/remove confirmation, and success/error feedback.
- Laravel must validate authorization, ownership, MIME type, file size and safe storage. Inertia exposes only the resolved branding data or authorized asset URL needed by React; the browser must not determine a user's institution from untrusted input.
- Branding must maintain usable contrast and sizing in Premium Light, Premium Dark, Ocean Blue and Emerald. Prefer transparent SVG/PNG assets when approved, preserve aspect ratio, provide accessible alternative text, and never stretch or recolor an uploaded official logo as a theme substitute.

## 4. Icons — Mandatory Professional Usage
Use one consistent icon family throughout the application. Prefer the icon set already supplied by the Laravel React starter kit; if a library is needed, use Lucide React consistently.

Rules:
- use icons to reinforce meaning, not decorate randomly
- buttons with common actions should have appropriate icons when useful: Add, Edit, Delete, Save, Search, Filter, Export, Download, Upload, Refresh, View, More, Back, Close
- success/warning/error/info messages must use matching semantic icons
- sidebar module items must use stable icons and must not change icon meaning between pages
- icon-only buttons require tooltip and accessible label
- destructive icons/actions must use destructive semantic styling
- icon size/alignment must be consistent
- do not mix unrelated icon families unless formally approved

## 5. Loading Experience — Mandatory
No action that waits on navigation, network, mutation, file processing or data refresh may appear frozen.

Use the correct loader for the context:
- page navigation: top progress indicator and/or page-level skeleton
- initial table/data load: skeleton rows/cards, not a blank page
- button action: inline spinner + disabled button + action text such as “Saving…”
- modal/drawer submit: keep container visible; show pending state without layout jump
- select/autocomplete remote load: inline compact loader
- file upload/import/export: progress indicator when progress is available
- long-running processing: progress/status panel when possible

Rules:
- prevent duplicate submissions while pending
- preserve the previous useful content during background refresh when safe
- use skeletons that resemble the final layout
- avoid full-screen blocking loaders for small local actions
- loaders must stop on both success and failure
- communicate long-running states in plain language

## 6. Toasts, Alerts and Messages — Mandatory
Every meaningful user action must provide professional feedback.

### Success
Use a concise success toast after completed mutations.
Examples: “University profile updated.”, “Student saved successfully.”
Do not show success before the server confirms completion.

### Error
Show an error toast/banner for server/network/action failures and preserve actionable form data.
Use plain language and, when useful, a retry action.
Never expose stack traces, SQL errors or internal exception details to end users.

### Validation
Field validation errors belong next to the affected field.
Also focus/scroll to the first invalid field on submit when practical.
A generic toast may summarize that validation failed, but it must not replace field-level errors.

### Warning
Use warning alerts for risky but recoverable states.

### Info
Use info banners/toasts for contextual non-error information such as policy notes, pending approvals, sync states or read-only conditions.

### Confirmation
Destructive or irreversible actions require a confirmation dialog with clear object/action wording.
Prefer specific text: “Delete Academic Session?” rather than “Are you sure?”

## 7. Empty, No-Result and Error States
Every data region must define:
- first-use empty state with useful explanation and permitted CTA
- filtered/search no-results state with a clear reset action
- permission-restricted state when applicable
- recoverable loading error with retry
- 403, 404, 419/session-expired and 500-class friendly pages

Never show an empty white box or raw exception.

## 8. Forms
Forms must use reusable components and consistent layout.
Requirements:
- visible labels; placeholders are not labels
- required/optional indication
- concise helper text where needed
- server-side Laravel validation is authoritative
- React shows mapped validation errors clearly
- dependent fields show disabled/loading/empty states correctly
- searchable selects for large master lists
- date/time pickers where appropriate
- sensible default values only when business-safe
- cancel/back behavior must be predictable
- warn about unsaved changes where loss is realistic
- disable or protect submit while pending
- preserve entered data after validation/server error when possible

### Shared Date Picker Standard
- Date fields must use the shared theme-aware `DatePicker` rather than inconsistent browser-native controls or page-specific calendars, unless a documented accessibility constraint requires a native control.
- Provide direct month and year selection together with previous/next month navigation; never force users to traverse long date ranges one month at a time.
- Date bounds (`min` / `max`) must follow business rules, visibly disable unavailable dates, and be repeated in Laravel validation.
- Persist date-only business values as database `DATE` values and serialize them to Inertia as `YYYY-MM-DD` to prevent timezone drift.
- Preserve semantic tokens, keyboard/focus behavior, accessible month/year labels, selected/disabled states, responsive dialog behavior, and all four built-in themes.

## 9. Tables and Data-Heavy Screens
Standard data tables should support only the capabilities relevant to the page, chosen from:
- keyword search
- advanced filters
- sorting
- server pagination
- page size where justified
- column visibility where justified
- bulk selection/actions when permitted
- row actions in a consistent menu
- status badges
- sticky header for long tables when useful
- responsive overflow or mobile alternate layout
- export when specified by the business page
- loading skeletons
- empty/no-result/error states

Large datasets must use server-side pagination/filtering/sorting rather than loading everything into the browser.

## 10. Modals, Drawers and Pages
Use behavior based on task complexity:
- modal: short confirmation or compact focused form
- drawer/sheet: contextual create/edit/details without losing list context
- full page: complex multi-section forms, workflows, reports or deep details

Do not put long complex forms into tiny dialogs.
Focus must be trapped correctly in modal/drawer and returned to the triggering control on close.

## 11. Interaction and Motion
Transitions must be subtle and purposeful.
- hover/focus/pressed/disabled states are mandatory for interactive controls
- use short transitions for drawers, menus and state changes
- avoid excessive animation
- respect reduced-motion preferences
- never delay a user action merely for animation

## 12. Responsive Behavior
Every implementation must explicitly handle desktop, tablet and mobile.
Desktop should optimize information density; mobile should prioritize essential actions and readable content.
Sidebars collapse appropriately. Tables may scroll or transform where needed. Forms should avoid unusably narrow multi-column layouts.

## 13. Accessibility
Minimum requirements:
- keyboard navigable controls
- visible focus states
- semantic labels
- accessible icon-only controls
- proper dialog semantics
- adequate color contrast
- state not communicated by color alone
- screen-reader friendly validation and status feedback

## 14. Permissions and Behavioral UI
React may hide/disable actions based on permissions for good UX, but Laravel authorization is always authoritative.
If a user lacks an action permission, do not display a misleading enabled control.
Scope-sensitive screens must visibly communicate current University/College/Campus/Department context when ambiguity is possible.

## 15. Inertia Navigation and Forms
This application uses Inertia rather than a separate SPA REST layer for normal internal ERP pages.
Prefer Inertia Link/router/form patterns for page navigation and standard CRUD/workflows.
Use Laravel controllers, Form Requests, policies and services/actions on the server.
Use dedicated JSON/API endpoints only when a feature genuinely requires them (for example external integrations, async widgets, public API, or realtime support).

## 16. Required Reusable Components
Before duplicating behavior, use or create reusable components for:
- AppShell
- Sidebar / NavGroup / NavItem
- TopBar
- Breadcrumbs
- PageHeader
- SectionCard
- StatCard
- DataTable
- FilterBar
- Pagination
- FormField
- SearchableSelect
- StatusBadge
- EmptyState
- ErrorState
- Skeleton variants
- Spinner
- Progress indicator
- Toast/notification
- Alert/InfoBanner
- ConfirmDialog
- Modal/Dialog
- Drawer/Sheet
- Tooltip
- Permission-aware action wrapper/helper
- InstitutionBrand / InstitutionLogo

## 17. Page Definition of Done — UI/UX
A page is not complete until:
- visual hierarchy matches the design system
- icons are consistent and meaningful
- loading states exist for initial load and mutations
- success, error, warning and information feedback are defined where relevant
- validation errors are clear
- empty/no-result/error states are handled
- destructive actions confirm properly
- buttons prevent duplicate actions while pending
- desktop/tablet/mobile behavior is verified
- keyboard/focus behavior is usable
- permissions are reflected in UI and enforced by Laravel
- no raw backend/internal error is shown to the user
- shared components are reused rather than duplicated

### Hierarchical Sidebar Tree Standard
For the authenticated ERP navigation:
- represent nested navigation with clear but restrained tree connector lines
- use accordion behavior so only one sibling branch at the same depth is expanded
- automatically expand ancestors of the current route
- distinguish active leaf state from expanded parent state
- use short expand/collapse and chevron transitions only; no decorative or delayed motion
- respect `prefers-reduced-motion`
- use semantic theme tokens for lines, text, active/hover/focus/expanded states
- never add a sidebar item merely for visual completeness; navigation follows implemented, approved hierarchy only


### Master Table Visual Consistency — 2026-08-22
- Curriculum Header and Terms / Semesters follow the established Permission Catalog table treatment.
- Use shared `Card`, `Button`, `Input`, and `Select` components where applicable.
- Table header: `border-b bg-muted/50 text-left`.
- Row: `border-b transition-colors last:border-0 hover:bg-muted/40`.
- Standard table cell spacing: `px-4 py-4`.
- ACTIVE uses the existing emerald status pill.
- INACTIVE and RETIRED use the existing muted status pill.
- DRAFT uses an amber working-state pill.
- Row actions use compact `size="sm"` ghost Buttons with icon + visible text.
- Destructive lifecycle actions use destructive text while retaining the same compact action layout.
- New academic modules must reuse this established table/action language instead of introducing one-off styling.


### Curriculum Slots Phase 1 UI — 2026-08-22
- Use the same Permission Catalog table language already adopted by Curriculum Header and Terms / Semesters.
- Active = emerald status pill; Inactive = muted status pill.
- Edit = Pencil icon + text; Set Inactive = Circle-X destructive text; Set Active = Rotate-Ccw + text.
- Slot creation/editing uses shared `Button`, `Card`, `Input`, and `Select`.
- Slot page is contextual to a selected Term / Semester; do not add a context-free sidebar leaf.


### Curriculum Slots Phase 2 UI — 2026-08-22
- Continue the Permission Catalog table/action language already adopted by Curriculum pages.
- Use existing shared Select controls for Course Category, Course Type and Selection Rule.
- Min/Max inputs are shown only when `Choice` is selected.
- Mandatory/Choice is displayed as readable text in the table; Choice also shows the Min-Max selection range.
- Do not introduce Slot Credit into the form.


### Course / Paper Mapping UI — 2026-08-22
- Mapping page follows the Permission Catalog table/action language already used by Curriculum pages.
- Slot row exposes compact `Courses` contextual action.
- Active mapping = emerald status pill; Inactive = muted pill.
- Status action uses compact icon + text ghost Button.
- Add Mapping uses shared Select and Button components.
- No context-free Course Mapping sidebar item.


### Mapping Display Order UI — 2026-08-22
- Mapping order is edited inline in the existing Course / Paper Mapping table.
- Use the same compact table typography and spacing as the Permission Catalog reference.
- Read-only curricula display the order value without an editable control.
- No drag-and-drop library is introduced for this milestone.


### Mapping Context UI — 2026-08-22
Mapping form order is Discipline -> optional Specialization -> Course / Subject. Discipline is limited to Program Template selections; Specialization is dependent and optional.


### Course Mapping Edit Action — 2026-08-22
DRAFT mapping rows use the standard compact Pencil + Edit action. The same mapping dialog opens prefilled with current values.


### Curriculum-Wide Validation Placement — 2026-08-22
Curriculum-wide actions belong in the Manage Structure header. Slot/Course Mapping pages must expose only contextual actions for that Slot. Therefore Validate Structure is shown on Manage Structure and removed from Course / Paper Mapping.


### Curriculum Slot Credits UI — 2026-08-22
- Credits appear in the existing Curriculum Slot create/edit dialog.
- Use the existing Input component and table typography.
- Credits also appear as a compact column in the Slot table and as read-only Slot context on Course / Paper Mapping.
- No separate sidebar item is created for Slot Credit Binding.


### Terms / Semesters Action Layout — 2026-08-22
- Section actions are grouped on the right side of the Terms / Semesters card header.
- Order: `Validate Structure` then `Add Term / Semester`.
- `Slots` remains the first contextual action in each Term row.
- Navigation actions such as `Slots` must not disappear just because the Curriculum is read-only.


### Credit Summary UI — 2026-08-22
- Credit Summary is shown at Curriculum Manage Structure level.
- Show Curriculum Required Credits and Maximum Credits first.
- Show Term/Semester Required and Maximum totals below.
- Keep it read-only; Credits are edited only through Curriculum Slots.


### Curriculum Sidebar Simplification — 2026-08-22
- Remove redundant `Curriculum Header` child.
- `Curriculum` itself opens `/admin/curricula`.
- All Curriculum header and structure workflow stays inside the Curriculum module.


### Clone Actions — 2026-08-22
- Curriculum list: `Clone Structure`.
- Terms table: compact `Copy + Clone Semester`.
- Slots table: compact `Copy + Clone Slot`.
- Clone actions reuse existing action typography/icons and do not create sidebar items.
- Confirmation/configuration uses the same modal language and spacing as existing Curriculum/Structure forms.


### Entire Curriculum Delete — 2026-08-22
- Show `Trash2 + Delete` only for DRAFT Curriculum with update permission.
- Use destructive text styling consistent with existing actions.
- Require the user to type the exact Curriculum Code before submitting permanent deletion.
- Never show Delete for ACTIVE/RETIRED Curriculum.
- After assignment functionality exists, hide Delete for assigned Curriculum as well.


### Curriculum List Delete Visibility — 2026-08-22
For a DRAFT Curriculum with `curriculum.update`, the Actions area must show `Trash2 + Delete` alongside Structure / Clone / Edit. ACTIVE and RETIRED rows must not show Delete.


### Curriculum Row Actions — Final Compact Standard (2026-08-22)
Curriculum list must not render every lifecycle action as horizontal text.

Visible:
- `Structure`
- `Edit` when editable

`More` menu:
- `Submit for Approval` only after a current recorded Structure Validation PASS
- `Clone Structure`
- `Delete`
- `Retire`
- `Restore` when applicable

If the user has submit permission and the Curriculum is otherwise eligible but validation is missing/stale, show a non-actionable `Validate Structure First` hint in the More menu instead of an enabled Submit action.

Laravel remains authoritative for validation, permissions and lifecycle restrictions.


### Curriculum More Menu Portal Fix — 2026-08-22
The Curriculum `More` menu must render through a document-body portal with fixed positioning. It must never expand the table row, create internal table scrolling, or be clipped by a Card/table overflow container. Clicking outside closes the menu.

The row remains compact:
`Structure | Edit | More`.

Submit readiness is supplied authoritatively by Laravel as `can_submit_for_approval`; the frontend must not reconstruct the complete business rule independently.


### Test Data Cleanup UI — 2026-08-22
- Never expose a raw DB console or arbitrary table truncate UI.
- Show test entities in a normal ERP table with dependency counts.
- Destructive action uses `Eraser` icon + destructive styling.
- Require exact entity code confirmation.
- Disable action when environment safety or downstream-reference checks fail.

## Scalable configuration menus

For a master record with multiple growing configuration subsections, do not place every subsection as a separate table-row button.

Use:
`Configure → subsection menu`

Keep independent lifecycle actions such as Edit, Validate, Retire or Approval separate when they operate on the parent record as a whole.

Academic Policies is the reference implementation of this pattern.
