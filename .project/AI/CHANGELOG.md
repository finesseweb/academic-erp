
## 2026-08-20 — NEXT batch precedence conflict removed
- Removed remaining contradictory frozen-workflow wording that forced every `next` command to one milestone.
- `PROJECT_CONSTITUTION.md` now explicitly treats `next N` / `nextN` as approval for N sequential milestones in one run.
- `PAGE_IMPLEMENTATION_REGISTRY.md` now supports multi-milestone advancement for approved batches.
- Added precedence wording so generic singular approval rules cannot override a numeric batch command.
# Project Changelog

## 2026-08-21 — Program Template Academic Hierarchy Refinement

- Replaced the ambiguous mixed Discipline/Specialization selector with a required top-level Discipline selector and an optional dependent Specialization selector.
- Added `program_templates.specialization_id`; the migration safely normalizes historical specialization-only selections into their parent Discipline plus Specialization.
- Enforced same-University, active-record and parent-match validation in Laravel.
- Prevented a Discipline with existing Specializations from being converted into a Specialization in both the UI and backend validation.
- Updated the Program Templates and Disciplines page specifications plus schema catalog, relationship map and table specifications.

## 2026-08-21 — University Academic Masters Batch

- Implemented Degrees, Disciplines / Specializations, Program Templates and Course Categories in frozen hierarchy order.
- Added normalized University-owned tables, explicit parent constraints, stable per-University codes, ordering and non-destructive lifecycle.
- Added sixteen University-scoped permissions, transactional audit events, permission-aware navigation, Inertia routes and focused feature tests.
- Added a reusable premium academic-master page with semantic theme tokens, accessible dialogs, inline validation, pending controls, empty states and lifecycle confirmations.
- Passed 92 tests / 539 assertions plus TypeScript, ESLint, Prettier, Pint, route inspection, local MySQL migrations and Vite production build.
- Updated page/table specs, schema catalog, relationships, permission/audit catalogs, registry and current state. Course Types is next.

## 2026-08-21 — College Audit and Academic Setup Batch

- Implemented exact-scope, read-only College Access Audit and canonical College scope persistence for delegated access events.
- Implemented University Academic Sessions with date validation, lifecycle, audit history and transactional single-current selection.
- Implemented ordered University Degree Levels with non-destructive lifecycle, RBAC and audit history.
- Added three page specifications, two table specifications, schema/relationship/security catalog updates, migrations, routes, services, Inertia pages and feature tests.
- Passed 88 tests / 515 assertions plus TypeScript, ESLint, Prettier, Pint, route inspection, local MySQL migrations and Vite production build.
- Stopped after exactly three milestones. Degrees is next.

## 2026-08-20 — College Delegated RBAC Batch

- Implemented College Role Permissions using an explicit `is_college_delegable` allow-list and actor-held same-College grant checks.
- Implemented College User Role Assignment for active custom roles owned by the same College.
- Implemented fixed-College assignment lifecycle status and inclusive effective dates.
- Added sensitive College permission assign/remove, role assign/unassign and scope-update capabilities.
- Added backend denial for University permissions, non-held delegated permissions, cross-College users/roles/assignments and self-escalation.
- Added transactional audit coverage and dedicated cross-College security tests.
- Passed 83 tests / 459 assertions plus TypeScript, ESLint, Prettier, Pint, route inspection, local MySQL migration and Vite production build.
- Did not implement College Access Audit; it remains next.

## 2026-08-20 — College Admin Assignment, College Users and College Roles

- Added protected single-College `COLLEGE_ADMIN` assignment through the central login/RBAC foundation.
- Added primary College ownership for College Staff and exact-scope permission resolution/navigation.
- Implemented College-scoped staff list/create/edit/status/password-reset workflows and College-owned custom role list/create/edit/status workflows.
- Added eleven College access permissions, Super Admin delegation, scoped SQL queries and cross-College tampering tests.
- Passed 81 tests / 433 assertions plus TypeScript, ESLint, Prettier, Pint, route inspection, local MySQL migration and Vite production build.
- Deferred permission delegation to the next College Role Permissions milestone.

## 2026-08-20 — Scope Assignment

- Added permission-aware editing of an existing user-role assignment's explicit University or active affiliated-College scope on the shared User Access page.
- Added Active/Inactive lifecycle and optional inclusive effective-date controls using the shared theme-aware date picker.
- Persisted sensitive `scope.update`, added an effective-access index, and enforced active/not-yet-started/expired assignment filtering during permission resolution.
- Blocked invalid College references, duplicate role/scope combinations, protected system-assignment changes and self-scope escalation.
- Added transactional `USER_ROLE_SCOPE_UPDATED` audit history with safe before/after scope and lifecycle data.
- Passed 77 tests / 392 assertions plus TypeScript, ESLint, Prettier, Pint, route inspection, local MySQL migration, and the Vite production build.
- Did not implement Audit Logs; it remains the next milestone.

## 2026-08-19 — Institution Branding Documentation Standard

- Defined a single reusable, backend-resolved institution-branding contract for University- and College-scoped users.
- Standardized logo/name/code precedence, accessible constrained-space behavior, non-Laravel fallbacks, four-theme compatibility, upload safeguards, and reuse across approved application and document surfaces.
- Recorded University Profile and Affiliated College Branding as the authoritative future upload locations without marking deferred branding storage or UI as implemented.
- Scope Assignment remains the next eligible implementation milestone.

## 2026-08-20 — User Role Assignment

- Implemented per-user access administration with current assignment cards, custom-role selection, explicit University or validated affiliated-College scope, and effective permission preview.
- Added confirmation-based unassignment, duplicate prevention, protected system-role assignment controls, and self-unassignment safety.
- Persisted sensitive `role.assign` and `role.unassign` capabilities and added role/status plus scope/status assignment indexes.
- Added transactional `USER_ROLE_ASSIGNED` and `USER_ROLE_UNASSIGNED` audit events containing the role code and canonical scope.
- Passed 73 tests / 374 assertions plus TypeScript, ESLint, Prettier, migration, route, and production-build checks.
- Did not implement editing existing assignment scope/effective dates; Scope Assignment remains next.

## 2026-08-20 — Role Permission Matrix

- Implemented the Role Permission Matrix with administrator-friendly permission names, secondary technical codes, search, expandable module groups, select/clear controls, sensitive badges, selected counts, and unsaved-change indicators.
- Added reviewed add/remove summaries and confirmation before applying changes.
- Persisted sensitive `permission.assign_to_role` and `permission.remove_from_role` capabilities and added the reverse permission lookup index.
- Validated every submitted ID against the active catalog, enforced add/remove authorization from the actual diff, and kept protected system-role grants migration-controlled/read-only.
- Added transactional `ROLE_PERMISSIONS_UPDATED` audit events containing added and removed permission codes.
- Passed 70 tests / 349 assertions plus TypeScript, ESLint, Prettier, migration, route, and production-build checks.
- Did not implement user-role or scope assignment; User Role Assignment remains next.

## 2026-08-19 — Permission Catalog

- Implemented the Permissions milestone as a read-only, responsive catalog of backend capabilities.
- Added search, module/status/sensitivity filters, summaries, server pagination, semantic status metadata, empty/no-result states, and permission-aware navigation.
- Persisted `permission.view` for Super Admin and added the documented module/status catalog index.
- Added authorization, filtering, Inertia-prop, and explicit no-mutation-route coverage.
- Passed 66 tests / 327 assertions plus TypeScript, ESLint, Prettier, migration, route, and production-build checks.
- Did not implement role-permission assignment; the Role Permission Matrix remains the next milestone.

## 2026-08-19 — Role Administration

- Implemented the Roles milestone with responsive list, create, edit/view, search, type/status filtering, counts, pagination, and confirmation-based lifecycle controls.
- Added persisted `role.view`, `role.create`, `role.update`, and `role.disable` permissions plus the documented owner-scope/status index.
- Protected system roles, including `SUPER_ADMIN`, from metadata changes and deactivation while allowing safe read-only inspection.
- Added transactional `ROLE_CREATED`, `ROLE_UPDATED`, and `ROLE_STATUS_CHANGED` audit events.
- Passed 63 tests / 306 assertions plus TypeScript, ESLint, Prettier, routes, migration, and production-build checks.
- Did not implement permission catalog management or role-permission assignment; those remain the next ordered milestones.

## 2026-08-19 — User Administration

- Implemented the Users milestone with responsive list, create, edit, search, role/status/account-type filters, pagination, account summaries, status confirmation, and secure reset-link initiation.
- Added user mobile, controlled account type, active/inactive lifecycle, last-login metadata, and a status/account-type/reporting index.
- Persisted and enforced `user.view`, `user.create`, `user.update`, `user.disable`, `user.enable`, and `user.reset_password`, granting them to Super Admin.
- Added inactive-account middleware, session revocation on disable, self-disable prevention, and protected Super Administrator disable prevention.
- Added transactional audit events and passed 59 tests / 277 assertions plus TypeScript, ESLint, Prettier, route, migration, and production-build checks.
- Role and scope assignment remain read-only summaries until their explicitly ordered milestones; did not advance to Roles.

## 2026-08-19 — Authorized Signatories

- Implemented the third University Foundation milestone with responsive list, create, edit, filter, pagination, summary, and activate/deactivate workflows using Laravel Inertia routes.
- Added University-owned `authorized_signatories` appointment records with controlled authority categories, official contact details, effective date range, status, notes, and reporting indexes.
- Persisted and enforced four action-specific permissions and transactional create/update/status audit events.
- Reused the shared ERP date picker with direct month/year selection, semantic theme tokens, inline validation, pending states, confirmation dialogs, and permission-aware navigation/actions.
- Passed 54 tests / 240 assertions, TypeScript, ESLint, Prettier, and production build checks.
- Deferred signature specimen/image upload until protected document storage policy is specified; did not advance to Users.

## 2026-08-19 — Affiliated Colleges

- Implemented the second University Foundation milestone: responsive Affiliated College list, create, edit, and activate/deactivate workflows using Laravel Inertia routes.
- Added the University-owned `colleges` entity with stable unique code, governed affiliation/status, official contact/address, timezone, optional linked Principal account, and query-driven indexes.
- Persisted and enforced `college.view`, `college.create`, `college.update`, and `college.disable`; granted them to the protected Super Admin role.
- Added server pagination/search/filters, summary cards, first-use and no-result states, confirmations, pending states, inline validation, permission-aware navigation/actions, semantic tokens, and four-theme compatibility.
- Added transactional `COLLEGE_CREATED`, `COLLEGE_UPDATED`, and `COLLEGE_STATUS_CHANGED` audit events and feature coverage for permission denial, validation, filtering, CRUD, lifecycle, and audit behavior.
- Did not implement College academic, finance, branding, campus, staff/user assignment, or other later milestones.

## 2026-08-18 — Step 00 Premium Authentication UI Foundation

- Replaced the generic Laravel React starter-kit authentication appearance with one shared Academic ERP authentication shell.
- Redesigned Login, Register, Forgot/Reset Password, Email Verification, Password Confirmation, Two-Factor Challenge, and the existing passkey authentication presentation without changing Laravel Fortify/Inertia behavior.
- Added enterprise branding, responsive desktop split/mobile layouts, semantic themed surfaces, four-theme selection, accessible validation focus, inline status messages, meaningful icons, pending labels/spinners, and duplicate-submit prevention.
- Added reusable authentication field, status-message, and first-error-focus components rather than duplicating page styling.
- No database, route, authentication policy, credential, session, or authorization behavior changed.

## 2026-08-18 — University Profile

- Implemented the first University Foundation milestone as a Laravel/Inertia University Profile page.
- Added the singleton University entity, scoped `university.view` and `university.update` permission foundation, active role assignments, and immutable profile-update audit records.
- Added server validation, transactional updates, responsive/read-only/pending/validation/success UI states, and navigation from the authenticated shell.
- Replaced the starter light/dark/system appearance choices with the required Premium Light, Premium Dark, Ocean Blue, and Emerald semantic-token themes.
- Added feature coverage for authentication, permission denial, page props, validation, successful update, and audit creation.
- Affiliated Colleges remains the next unimplemented milestone.

## 2026-08-13 — Account Security and Theme Access

- Implemented authenticated self-service password change with current-password verification, 12-character minimum, salted scrypt hashing, other-session revocation and `PASSWORD_CHANGED` audit logging.
- Added global built-in theme policy and per-user theme preference persistence through named Laravel migrations / Eloquent migration `20260813183000_theme_access_and_password_security`.
- Added `theme.select_own` and `theme.manage_personal_selection`, seeded and granted to `SUPER_ADMIN`.
- Added authorized theme context, personal preference and global-policy REST endpoints.
- Moved theme controls from the header into `/admin`; users without both policy allowance and RBAC permission receive the administrator-selected default on login.
- Activated the account-menu Change Password route.
- Custom theme creation, role-management UI, institution policy resolution and user-management UI remain outside this milestone.

## 2026-08-13 — Super Admin Application Shell

- Added the protected `/admin` frontend shell using the existing refresh-session authentication flow.
- Added responsive sidebar/drawer navigation, desktop collapse persistence, nested navigation and active-route presentation.
- Added centralized permission-aware navigation based on backend-issued effective permission codes.
- Added the sticky application header with built-in theme selection, notification placeholder and authenticated account/logout menu.
- Added reusable `PageHeader`, `Breadcrumbs`, permission gate and code-native icon components.
- Added a temporary Super Admin landing state without dashboard statistics or business-module behavior.
- Extended all built-in theme definitions with semantic sidebar-active and topbar-background tokens.
- Retained the access token in memory for authenticated API calls and redirected successful or already-authenticated login sessions to `/admin`.
- No database schema, migration, backend endpoint, role, permission or audit-event changes were made.

## 2026-08-13 — University-affiliated-college hierarchy and fee governance
- Reframed ERP root as one University with multiple affiliated Colleges.
- Reworked Institution page specs into Affiliated College semantics.
- Added canonical University hierarchy documentation.
- Added layered University/College fee governance.
- Added College-level Installment Plans PAGE_SPEC and rules.
- Updated RBAC scope, permission catalog, architecture, database rules and Super Admin roadmap.
## University Domain Root Finalization
- University is now the canonical business root.
- SUPER_ADMIN remains the highest University-level role.
- Super Admin page-spec directory is now `PAGE_SPECS/UNIVERSITY_ADMIN/`.
- Generic Institution pages are now Affiliated College pages.
- Canonical documentation root remains `.project/AI/` and must always be read first.
- Theme hierarchy is User -> Affiliated College -> University Global -> Premium Light.
- College Fee Management includes College-level Installment Plans under University finance governance.
