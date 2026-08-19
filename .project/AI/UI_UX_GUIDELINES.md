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
