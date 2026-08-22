
## 2026-08-22 — Phase 5B UI Corrections

- Fixed Curriculum list table header alignment by restoring the Approval column header between Status and Actions.
- Fixed Course / Paper Mapping display so Course / Paper shows the subject/course name and Course Code shows the course code.
- Added a conservative Course Master legacy data repair for rows accidentally stored with `name` and `code` reversed.
- No approval workflow or database behavior changed in this UI correction.

## 2026-08-22 — Phase 5B Academic Approval Execution

- Added generic approval requests and snapshotted approval request stages.
- Added Curriculum approval_status lifecycle.
- Added Validate Structure -> Submit for Approval flow.
- Added Curriculum lock while submitted/under approval.
- Added role-based Approval Inbox using active/effective User Role assignments.
- Added Approve, Return and Reject decisions with stage remarks.
- Multi-level approval advances sequentially.
- Final approval automatically activates Curriculum.
- Return/Reject unlocks DRAFT Curriculum for correction/resubmission.
- Added Approval History.
- Added approval request permission family and Super Admin grants.
- Gate 5 now requires QA before starting Admissions.

## 2026-08-22 — Phase 5A Academic Approval Workflow Setup

- Started Governance Approval Workflow according to controlled SRS.
- Added generic approval workflow and ordered stage tables.
- Added Role Master based approver configuration; no Dean/Director/Registrar hard-coding.
- Added Academic Approval -> Workflow Setup navigation.
- Added workflow create, stage add and active/inactive controls.
- Added approval_workflow permission family and Super Admin grants.
- Added audit events for workflow/stage setup.
- Phase 5 remains IN PROGRESS; Curriculum submission/decision/publish is Phase 5B.
- Admissions must not start before Gate 5 PASS.

## 2026-08-22 — Curriculum Delete Action UI Fix

- Fixed Curriculum list action rendering so `Delete` is actually shown for DRAFT Curricula.
- Delete remains hidden for ACTIVE and RETIRED Curricula.
- Existing backend DRAFT safety, typed Curriculum Code confirmation and complete hierarchy deletion remain unchanged.
- No migration.

## 2026-08-22 — Entire Curriculum Safe Delete

- Added permanent delete for DRAFT Curriculum.
- Entire delete removes Course/Paper Mappings, Slots, Terms and then Curriculum in one DB transaction.
- Added strong typed-code confirmation in Curriculum UI.
- Delete is never shown for ACTIVE/RETIRED Curriculum.
- Backend independently enforces DRAFT lifecycle safety.
- Added `CURRICULUM_DELETED` audit event including deleted child counts.
- Preserved centralized future Student Group/Cohort assignment lock; no fake assignment table introduced.
- No database migration.

## 2026-08-22 — Professional Cross-Version Semester / Slot Clone

- Clone Semester can target same or another compatible DRAFT Curriculum version.
- Clone Slot can target same or another compatible DRAFT Curriculum version.
- Semester clone creates its Semester in target.
- Slot clone requires an existing target Semester.
- Cross-version target restricted to same University + same Program Template.
- Source may be DRAFT/ACTIVE/RETIRED; target must be DRAFT.
- No migration.

## 2026-08-22 — Slots Relationship Fix + Safe Delete Rules

- Fixed `CurriculumTerm::slots()` relationship causing RelationNotFoundException.
- Added DRAFT-only Delete for Terms, Slots and Course/Paper Mappings.
- Term deletion removes its Slots and their mappings.
- Slot deletion removes its mappings.
- Added delete audit events.
- Documented mandatory future lock after Student Group/Cohort assignment.
- No fake assignment table was introduced; the future guard point is centralized in CurriculumStructureDeleteService.

## 2026-08-22 — Complete Curriculum / Semester / Slot Clone

- Added complete Curriculum Structure Clone.
- Complete clone requires final source validation and creates a new DRAFT Curriculum.
- Complete clone copies Terms, Slots, Credits, selection rules, Course Mappings, Discipline/Specialization and display order with new IDs.
- Added DRAFT-only Clone Semester convenience action.
- Added DRAFT-only Clone Slot action with same/other Term target inside the same Curriculum.
- Credit Summary is intentionally not copied because it is derived from cloned Slot Credits.
- Added clone audit events.
- No new database migration.

## 2026-08-22 — Final Credit-aware Validation + Curriculum Sidebar Simplification

- Extended Curriculum-wide `Validate Structure` with Slot Credit validation.
- Active Slot without Credits fails with `SLOT_CREDITS_MISSING`.
- Invalid/non-numeric/negative Credit fails with `SLOT_CREDITS_INVALID`.
- Existing structural, mapping, Discipline/Specialization and Choice validation remains.
- Updated validation wording from Phase 1/non-credit to final Credit-aware validation.
- Removed redundant `Curriculum Header` submenu item.
- `Curriculum` is now the direct clickable sidebar item for `/admin/curricula`.
- Next implementation: Copy / Clone Structure.

## 2026-08-22 — Curriculum Credit Summary

- Added derived Credit Summary at Curriculum Manage Structure level.
- Mandatory Slot contributes its Slot Credit once.
- Choice Slot Required Credits = Slot Credit × Minimum Selection.
- Choice Slot Maximum Credits = Slot Credit × Maximum Selection.
- Added Term/Semester and Curriculum Required/Maximum totals.
- Reports active Slots missing Credits.
- No summary table added; totals remain derived from canonical Slot data.
- Next implementation: final Credit-aware Curriculum Validation.
- Copy / Clone remains deferred until final validation.

## 2026-08-22 — Terms Slots Action and Validation Placement Fix

- Restored the missing `Slots` navigation action on every Term / Semester row.
- `Slots` remains visible as a view/navigation action even when edit actions are unavailable.
- Moved `Validate Structure` from the page-level Curriculum identity header into the Terms / Semesters action bar.
- Positioned `Validate Structure` immediately left of `Add Term / Semester`.
- No Curriculum business rules, Credit logic, validation rules, routes or database schema changed.

## 2026-08-22 — Credit Binding Blank Screen Fix

- Fixed missing `ShieldCheck` Lucide import on Curriculum Manage Structure / Terms page.
- The missing import caused the page render to fail after moving Curriculum-wide Validation to the Manage Structure header.
- No Curriculum data model, Credit logic, validation logic, routes or database schema changed.
- Credit remains bound to Curriculum Slot.
- Next implementation remains Credit Summary.

## 2026-08-22 — Curriculum Slot Credit Binding

- Restored Credits to Curriculum Slots according to the original University ERP hierarchy.
- Added `curriculum_slots.credits` as decimal curriculum/version-specific data.
- New/edited Slots require Credits; existing historical rows migrate safely with nullable DB field.
- Credit is not added to reusable Course Master or duplicated on Course Mapping.
- Slot table/form and Course Mapping context display Credits consistently.
- Updated execution order to Credit Binding -> Credit Summary -> Final Credit-aware Validation -> Copy / Clone.
- Clone remains deferred to avoid later rework.

## 2026-08-22 — Curriculum Validation Moved to Curriculum Level

- Moved `Validate Structure` from Slot-scoped Course / Paper Mapping UI to Curriculum `Manage Structure`.
- Validation still checks the complete selected Curriculum.
- Course Mapping UI now contains only mapping-specific actions.
- Validation remains read-only and uses `curriculum.view`.
- No validation rules, Credit logic or database schema changed.

## 2026-08-22 — Curriculum Validation Phase 1 UI/Schema Fix

- Fixed invalid adjacent JSX that prevented Course / Paper Mapping from compiling.
- Wrapped Validate Structure and Map Course/Paper in the standard action container.
- Validate Structure is now a read-only visible action; mapping changes remain DRAFT-only.
- Added the validation result modal.
- Corrected validator schema to `curriculum_terms.sequence_no` and `curriculum_slots.selection_mode`.
- Reused `curriculum.view` authorization.
- No Credit/Credit Summary behavior added.

## 2026-08-22 — DRAFT Course Mapping Edit

- Added DRAFT-only Edit action for Course / Paper Mapping.
- Edit can correct Discipline, optional Specialization and Course / Subject.
- Reuses all Program Template Discipline/Specialization and Slot Course compatibility validation.
- Added `CURRICULUM_COURSE_MAPPING_UPDATED` audit event.
- Existing rows showing missing Discipline/Specialization context can now be repaired.
- Course Master remains unchanged.
- ACTIVE/RETIRED Curriculum mappings remain read-only.

## 2026-08-22 — Discipline / Specialization Aware Course Mapping

- Added Program Template Discipline and optional Specialization context to Curriculum Course Mapping.
- Kept Course Master independent, exactly as documented.
- Reused `program_template_disciplines` and `program_template_discipline_specializations`.
- Backend validates Program Template membership, same-University, active state and specialization parent relationship.
- Preserved Slot Category/Type compatibility, Mapping Display Order, permissions and DRAFT-only editing.
- No Credit introduced.

## 2026-08-22 — Curriculum Mapping Display Order

- Added Slot-relative `display_order` to Course / Paper Mapping.
- Existing mappings are backfilled in stable Slot/ID order during migration.
- New mappings automatically receive the next available order.
- Reordering one mapped Course/Paper re-sequences the selected Slot continuously.
- Added `CURRICULUM_COURSE_MAPPING_ORDER_CHANGED` audit event.
- Preserved DRAFT-only editing and existing Permission Catalog table/action styling.
- Did not introduce Credit, L-T-P/contact hours, credit totals or Structure Validation.

## 2026-08-22 — Curriculum Course / Paper Mapping

- Implemented Course / Paper Mapping under a selected Curriculum Slot.
- Added `curriculum_course_mappings` linking reusable Course / Subject Master records to Slots.
- Enforced same-University, ACTIVE Course, matching Course Category and matching Course Type.
- Prevented duplicate Course mapping inside the same Slot.
- Reused existing `curriculum.view` / `curriculum.update`; no new permission family.
- Preserved DRAFT-only editing and read-only ACTIVE/RETIRED curricula.
- Added `CURRICULUM_COURSE_MAPPED` and `CURRICULUM_COURSE_MAPPING_STATUS_CHANGED`.
- Added contextual `Courses` action to Slot rows.
- Did not add Mapping Display Order, Credit, L-T-P/contact hours, totals or Structure Validation.
- Next milestone: Mapping Display Order.

## 2026-08-22 — Curriculum Slots Phase 2

- Completed Slot academic behavior with Course Type, Mandatory/Choice, Minimum Selection and Maximum Selection.
- Reused the existing University Course Type master; no duplicate Course Type master was introduced.
- Choice Slots require Min/Max and enforce Maximum >= Minimum.
- Mandatory Slots clear Min/Max because every mapped course in the Slot is required.
- Explicitly kept Credit out of Curriculum Slots.
- Added migration columns/indexes without rebuilding the Phase 1 Slot table.
- Preserved existing `curriculum.view` / `curriculum.update` authorization, DRAFT-only editing, audit behavior and Permission Catalog UI consistency.
- Next milestone is Course / Paper Mapping.

## 2026-08-22 — Curriculum Slots Phase 1

- Implemented Curriculum Slots Phase 1 under a selected Term / Semester.
- Added `curriculum_slots` with Course Category, Slot Name, Display Order and ACTIVE/INACTIVE status.
- Reused existing University Course Category master by foreign key; no global Slot Master was introduced.
- Enforced same-University ACTIVE Course Category selection.
- Enforced unique Display Order within each Term / Semester.
- Reused `curriculum.view` and `curriculum.update`; no new permission family.
- Preserved DRAFT-only structure editing and historical read-only behavior for ACTIVE/RETIRED curricula.
- Added audit events `CURRICULUM_SLOT_CREATED`, `CURRICULUM_SLOT_UPDATED`, `CURRICULUM_SLOT_STATUS_CHANGED`.
- Added contextual `Slots` action to each Term / Semester row.
- Explicitly excluded Credits, Course Type, selection rules and Course / Paper Mapping from Slot Phase 1.
- Approved Copy / Clone Structure as the future reuse pattern: copy structure into independent target records rather than sharing Slot IDs across curriculum versions.

## 2026-08-22 — Curriculum Table and Action Visual Consistency

- Aligned Curriculum Header and Terms / Semesters with the existing Permission Catalog table treatment.
- Standardized table header, row borders/hover, cell spacing and shared Card/Button controls.
- Standardized ACTIVE to the existing emerald badge and INACTIVE/RETIRED to muted badges.
- Kept DRAFT as an amber working-state badge.
- Standardized row actions to compact icon + text ghost buttons.
- Preserved existing routes, permissions, DatePicker behavior, lifecycle logic and database design.

## 2026-08-22 — Curriculum Manage Structure: Terms / Semesters

- Implemented the first Curriculum Manage Structure child milestone: Terms / Semesters.
- Added `curriculum_terms`, scoped to one versioned Curriculum Header.
- Added required unique sequence and curriculum-specific Term / Semester name.
- Reused existing `curriculum.view` and `curriculum.update` permissions; no new permission family introduced.
- Added contextual `Manage Structure` action on Curriculum Header instead of a context-free sidebar route.
- Enforced DRAFT-only structure changes; ACTIVE and RETIRED curriculum versions remain read-only to preserve historical structures.
- Added create, update and ACTIVE/INACTIVE lifecycle without hard delete.
- Added `CURRICULUM_TERM_CREATED`, `CURRICULUM_TERM_UPDATED`, and `CURRICULUM_TERM_STATUS_CHANGED` audit events.
- Did not implement Slots, Credits, Course Mapping, totals, validation, or Academic Calendar dates.
- Next eligible Curriculum milestone: Curriculum Slots — Course Category, Slot Name and Display Order.

## 2026-08-22 — Curriculum Header Authorization and Navigation Fix

- Corrected Curriculum authorization to the ERP-standard `hasPermission()` mechanism.
- Added a non-destructive repair migration to register/activate `curriculum.*` permissions and grant them to active protected `SUPER_ADMIN`.
- Corrected Curriculum audit actor mapping to `audit_logs.actor_user_id`.
- Corrected Academic Session ordering to use the documented `starts_on` column.
- Changed sidebar label from `User & Access Management` to `Access Management`.
- Changed Curriculum from a direct leaf to the documented tree `Academic Setup -> Curriculum -> Curriculum Header`.
- Kept Terms/Semesters, Slots, Course Mapping and later Curriculum structure hidden until their approved milestone.
- Removed one-off embedded input CSS and kept Curriculum UI on semantic project theme tokens.

## 2026-08-22 — Curriculum Header Implementation Started

- Added `curricula` schema for University-owned versioned Curriculum Headers.
- Linked each header to one active same-University Program Template and Academic Session.
- Added unique University curriculum code and Program + Session + Version protection.
- Added DRAFT / ACTIVE / RETIRED lifecycle without hard deletion.
- Added `curriculum.view/create/update/disable` permissions, non-College-delegable, initially granted to Super Admin.
- Added controller, Form Requests, service layer, model, Inertia page, routes and hierarchical sidebar entry.
- Added `CURRICULUM_CREATED`, `CURRICULUM_UPDATED`, and `CURRICULUM_RETIRED` audit writes.
- Explicitly deferred Terms/Semesters, Slots, Course Mapping and credit validation to Curriculum Manage Structure.


## 2026-08-20 — NEXT batch precedence conflict removed
- Removed remaining contradictory frozen-workflow wording that forced every `next` command to one milestone.
- `PROJECT_CONSTITUTION.md` now explicitly treats `next N` / `nextN` as approval for N sequential milestones in one run.
- `PAGE_IMPLEMENTATION_REGISTRY.md` now supports multi-milestone advancement for approved batches.
- Added precedence wording so generic singular approval rules cannot override a numeric batch command.
# Project Changelog

## 2026-08-22 — Premium Hierarchical Sidebar Tree Interaction

- Refined the approved hierarchical sidebar into a visible tree with semantic connector lines.
- Added controlled accordion behavior so only one sibling branch at each depth remains expanded.
- Preserved automatic expansion of the active route's ancestor branch after navigation/refresh.
- Added restrained expand/collapse opacity/height motion and chevron rotation with reduced-motion support.
- Kept all styling theme-safe through semantic sidebar tokens; no hard-coded theme colors were introduced.
- Added no new ERP modules, routes, permissions or database changes; navigation remains limited to the implemented documented hierarchy.
- Updated navigation decision, hierarchy, implementation state, UI/UX and theming documentation.

## 2026-08-21 — Program Template Many-to-Many Academic Structure

- Replaced the Program Template single-Discipline/single-Specialization persistence model with `program_template_disciplines` plus nested `program_template_discipline_specializations`.
- Program Templates can now bind multiple top-level Disciplines, and every mapped Discipline can independently bind zero or more of its own Specializations.
- Added migration `2026_08_21_150000_make_program_template_disciplines_many_to_many`, preserving existing direct Discipline/Specialization mappings before removing old columns.
- Added explicit short MySQL foreign-key names (`ptd_*`, `ptds_*`) after MySQL rejected Laravel's generated identifier as longer than the 64-character limit.
- Reworked Program Template create/edit into a wide responsive two-pane selector with searchable/scrollable Discipline and Specialization panes, selected filtering and persistent per-Discipline selections.
- Reworked Program Template list display so `Academic Structure` is collapsed by default, shows Discipline/Specialization counts, expands inline for details and internally scrolls when mappings are large.
- Kept the existing Discipline/Specialization master hierarchy unchanged and reused its parent-child validation as the canonical specialization relationship.
- Added no separate CSS file; the revised interface uses existing semantic Tailwind/theme tokens and remains compatible with the application's configured themes.
- Updated Program Template/Discipline page specs, table specs, schema catalog, relationship map and current implementation state.

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

## 2026-08-21 — Course / Subject Master

- Implemented Course / Subject Master.
- Added University-scoped Course Category and Course Type relationships.
- Added permissions: `course.view`, `course.create`, `course.update`, `course.disable`.
- Added `/admin/courses` Academic Master page and sidebar permission contract.
- Corrected controller authorization to use existing ERP `hasPermission()` behavior rather than Laravel Gate `can()`.
- Kept Course Master independent from Program/Discipline/Specialization and curriculum-specific term, credit and L-T-P values.
- Marked Curriculum / Course Mapping as the next academic implementation milestone.

## 2026-08-22 — Hierarchical Sidebar Navigation

- Replaced the flat authenticated ERP navigation design with a hierarchy-based collapsible tree.
- Grouped University Profile, Affiliated Colleges and Authorized Signatories under Institution Setup.
- Grouped Users, Roles, Permissions and Audit Logs under User & Access Management.
- Grouped current academic masters under Academic Setup, with Degree Structure, Program Setup and Course Setup sub-branches.
- Grouped College Users, College Roles and College Access Audit under College Management when College scope exists.
- Preserved all current Laravel route URLs and existing backend permission codes.
- Added recursive permission filtering so empty parent branches disappear automatically.
- Added active-route ancestor expansion so the current page remains discoverable inside the tree.
- Documented the navigation rule in the frozen hierarchy and added decision record `006_HIERARCHICAL_SIDEBAR_NAVIGATION.md`.


## 2026-08-22 — Curriculum Validation Phase 1
- Added read-only `Validate Structure`.
- Validates Terms, active Slots, Choice min/max, active Course Mapping, continuous ordering, Course compatibility, Program Template Discipline and optional Specialization context.
- Old mappings missing Discipline are reported as errors and can be repaired using DRAFT Edit Mapping.
- No Credit/Credit Summary/L-T-P validation added.
- Copy / Clone Structure remains next.
