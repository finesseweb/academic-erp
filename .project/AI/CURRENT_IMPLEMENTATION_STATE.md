# CURRENT IMPLEMENTATION STATE

## Purpose
Live checkpoint for the automatic `next` workflow.

Repository reality is authoritative.
Codex must reconcile this file at the beginning of every `next` run and update it at the end.

## Allowed Status
- NOT_STARTED
- IN_PROGRESS
- IMPLEMENTED
- ACCEPTED
- BLOCKED
- VERIFY_REPOSITORY

## Current Verification Queue

### Step 00 — Authentication UI Foundation
- Premium shared authentication layout: IMPLEMENTED
- Login, Register, Forgot Password, Reset Password, Email Verification, Password Confirmation, Two-Factor Challenge, and supported Passkey authentication presentation: IMPLEMENTED
- Four-theme coverage (Premium Light, Premium Dark, Ocean Blue, Emerald): IMPLEMENTED
- Authentication behavior: unchanged; existing Laravel Fortify + React + TypeScript + Inertia flows preserved
- Review: PENDING_REVIEW

### University Foundation
- University Profile: IMPLEMENTED
  - Inertia page: `resources/js/pages/university/profile.tsx`
  - Laravel routes: `university.show`, `university.update`
  - Migration: `2026_08_18_120000_create_university_profile_foundation`
  - Review: PENDING_REVIEW
- Affiliated Colleges: IMPLEMENTED
  - Inertia pages: `resources/js/pages/colleges/index.tsx`, `create.tsx`, `edit.tsx`
  - Laravel controller: `CollegeController`; model/service/requests implemented
  - Migration: `2026_08_19_090000_create_affiliated_colleges`
  - Local MySQL apply: COMPLETE — verified as migration batch 3 on 2026-08-19
  - Permissions: `college.view`, `college.create`, `college.update`, `college.disable`
  - Review: PENDING_REVIEW
- Authorized Signatories: IMPLEMENTED
  - Inertia pages: `resources/js/pages/signatories/index.tsx`, `create.tsx`, `edit.tsx`
  - Laravel controller: `AuthorizedSignatoryController`; model/service/requests implemented
  - Migration: `2026_08_19_140000_create_authorized_signatories`
  - Local MySQL apply: COMPLETE — verified as migration batch 4 on 2026-08-19
  - Permissions: `authorized_signatory.view`, `authorized_signatory.create`, `authorized_signatory.update`, `authorized_signatory.disable`
  - Signature specimen upload: deferred pending protected document-storage policy
  - Verification: 54 tests / 240 assertions, TypeScript, ESLint, Prettier, and production build passed
  - Review: PENDING_REVIEW

### Access & Security
- Users: IMPLEMENTED
  - Inertia pages: `resources/js/pages/users/index.tsx`, `create.tsx`, `edit.tsx`
  - Laravel controller/service/requests: `UserController`, `UserAdministrationService`, `StoreUserRequest`, `UpdateUserRequest`
  - Migration: `2026_08_19_180000_add_user_administration_fields`; applied to local MySQL as batch 5
  - Account lifecycle, protected Super Admin safety, session revocation, reset-link initiation, RBAC, and audit implemented
  - Role/scope values are summaries only; mutation remains in the ordered assignment milestones
  - Verification: 59 tests / 277 assertions, TypeScript, ESLint, Prettier, and production build passed
  - Review: PENDING_REVIEW
- Roles: IMPLEMENTED
  - Inertia pages: `resources/js/pages/roles/index.tsx`, `create.tsx`, `edit.tsx`
  - Laravel controller/service/requests: `RoleController`, `RoleService`, `StoreRoleRequest`, `UpdateRoleRequest`
  - Migration: `2026_08_19_210000_add_role_administration_permissions`; applied to local MySQL as batch 6
  - Custom-role metadata/lifecycle implemented; protected system roles are immutable through normal administration
  - Permission configuration remains deferred to the ordered Role Permission Matrix milestone
  - Verification: 63 tests / 306 assertions, TypeScript, ESLint, Prettier, and production build passed
  - Review: PENDING_REVIEW
- Permissions: IMPLEMENTED
  - Read-only Inertia catalog: `resources/js/pages/permissions/index.tsx`
  - Laravel controller: `PermissionController`
  - Migration: `2026_08_19_230000_add_permission_catalog_access`; applied to local MySQL as batch 7
  - Search, module/status/sensitivity filters, pagination, summary metadata, and permission-aware navigation implemented
  - No permission mutation routes exist; capabilities remain code/migration-defined
  - Verification: 66 tests / 327 assertions, TypeScript, ESLint, Prettier, and production build passed
  - Review: PENDING_REVIEW
- Role Permission Matrix: IMPLEMENTED
  - Inertia page: `resources/js/pages/roles/permissions.tsx`
  - Laravel controller/service: `RolePermissionController`, `RolePermissionService`
  - Migration: `2026_08_20_090000_add_role_permission_matrix_access`; applied to local MySQL as batch 8
  - Friendly grouped matrix, search, module select/clear, sensitive badges, diff preview, confirmation, protected system-role read-only state, and transactional audit implemented
  - Permissions: `permission.assign_to_role`, `permission.remove_from_role`
  - Verification: 70 tests / 349 assertions, TypeScript, ESLint, Prettier, and production build passed
  - Review: PENDING_REVIEW
- User Role Assignment: IMPLEMENTED
  - Inertia page: `resources/js/pages/users/roles.tsx`; accessible through User `Access` action
  - Laravel controller/service/model: `UserRoleController`, `UserRoleService`, `UserRole`
  - Migration: `2026_08_20_120000_add_user_role_assignment_access`; applied to local MySQL as batch 9
  - University/validated College assignment, permission preview, duplicate/system/self-removal safeguards, confirmation, and transactional audit implemented
  - Permissions: `role.assign`, `role.unassign`
  - Verification: 73 tests / 374 assertions, TypeScript, ESLint, Prettier, and production build passed
  - Review: PENDING_REVIEW
- Scope Assignment: IMPLEMENTED
  - Reuses `resources/js/pages/users/roles.tsx` with per-assignment scope/lifecycle editing
  - Laravel request/controller/service: `UpdateUserRoleScopeRequest`, `UserRoleController@updateScope`, `UserRoleService@updateScope`
  - Migration: `2026_08_20_150000_add_scope_assignment_access`
  - Local MySQL apply: COMPLETE — migration applied on 2026-08-20
  - Permission: sensitive `scope.update`
  - University/active-College scope validation, status/effective dates, duplicate/system/self safeguards, transactional audit, and runtime effective-period permission enforcement implemented
  - Verification: 77 tests / 392 assertions, TypeScript, ESLint, Prettier, Pint, route inspection, migration, and production build passed
  - Review: PENDING_REVIEW
- Audit Logs: IMPLEMENTED
  - Read-only Inertia report, sensitive `audit.view`, filters/details, migration and tests verified from repository reality

### College Access & Role Management
- College Administrator Login / Assignment: IMPLEMENTED
- College Users: IMPLEMENTED
- College Roles: IMPLEMENTED
- College Role Permissions: IMPLEMENTED
  - Reuses the shared role-permission matrix with College delegation context
  - Enforces `is_college_delegable`, actor-held grants, role ownership and cross-College denial
- College User Role Assignment: IMPLEMENTED
  - Inertia page: `resources/js/pages/college-users/roles.tsx`
  - Same-College custom role assignment/removal, preview, duplicate/self/cross-College safeguards and audit implemented
- College Scope Assignment: IMPLEMENTED
  - Fixed-College assignment status/effective-date editing and runtime lifecycle enforcement implemented
- Migration: `2026_08_20_210000_create_college_access_foundation`
- Delegation migration: `2026_08_20_235000_add_college_delegated_access_permissions`
- Delegation migration local MySQL apply: COMPLETE — applied on 2026-08-20
- Local MySQL apply: COMPLETE — migration applied on 2026-08-20
- Security: exact College-scoped permission checks and cross-College denial are backend enforced
- College Access Audit: IMPLEMENTED
  - Exact College-scoped immutable report, filters, delegated audit scope persistence and cross-College denial
  - Permission: sensitive, non-delegable `college_audit.view`
- Verification: included in batch verification below
- Review: PENDING_REVIEW

### University Academic Setup
- Academic Sessions: IMPLEMENTED
  - University-scoped CRUD/lifecycle/current selection, shared modern date picker and audit trail
  - Migration: `2026_08_21_100000_create_academic_sessions_table`
- Degree Levels: IMPLEMENTED
  - University-scoped ordered classifications, lifecycle, permissions and audit trail
  - Migration: `2026_08_21_110000_create_degree_levels_table`
- Batch verification: 88 tests / 515 assertions, TypeScript, ESLint, Prettier, Pint, route inspection, local MySQL migrations and production build passed
- Degrees: IMPLEMENTED
  - University-owned awards linked to active Degree Levels; lifecycle, RBAC and audit implemented
- Disciplines / Specializations: IMPLEMENTED
  - University-owned discipline/specialization hierarchy, lifecycle, RBAC and audit implemented
- Program Templates: IMPLEMENTED
  - Degree-linked University blueprint with many-to-many top-level Disciplines; each mapped Discipline supports zero-to-many same-Discipline Specializations
  - Scalable wide two-pane create/edit Academic Structure selector with Discipline/Specialization search, independent selections and responsive stacking
  - Compact list view with collapsed-by-default expandable Academic Structure summary/details and controlled scrolling for large mappings
  - Uses existing semantic Tailwind/theme tokens; no separate Program Template CSS file introduced
  - Migration: `2026_08_21_150000_make_program_template_disciplines_many_to_many`; preserves prior single-Discipline mappings, uses MySQL-safe short FK names and is applied to local MySQL
- Course Categories: IMPLEMENTED
  - Controlled classification groups, ordering, lifecycle, RBAC and audit implemented
- Academic masters batch verification: 92 tests / 539 assertions, TypeScript, ESLint, Prettier, Pint, route inspection, local MySQL migrations and Vite production build passed
- Course Types: VERIFY_REPOSITORY
- Course / Paper Master: VERIFY_REPOSITORY
- Curriculum Header: IMPLEMENTED_IN_REPLACEMENT_PACKAGE — permission repair + hasPermission authorization fix included; repository run/migration validation pending
- Curriculum Manage Structure:
  - Terms / Semesters: IMPLEMENTED_IN_REPLACEMENT_PACKAGE — repository migration/build validation pending
  - Slot Category / Slot Name / Display Order: VERIFY_REPOSITORY
  - Slot Course Type / Selection Rules: IMPLEMENTED_IN_REPLACEMENT_PACKAGE — repository migration/build validation pending
  - Slot Credit: NOT IMPLEMENTED / NOT PART OF CURRENT SLOT DESIGN
  - Course Mapping: VERIFY_REPOSITORY
  - Mapping Display Order: VERIFY_REPOSITORY
  - Credit Summary: VERIFY_REPOSITORY
  - Structure Validation: VERIFY_REPOSITORY

### Later Modules
Remain planned unless repository inspection proves otherwise.

## Next Eligible Milestone

Course Types is the next eligible milestone in the frozen hierarchy.

## Approved Deferred Cross-Cutting Standards

- Institution branding: PLANNED/NOT IMPLEMENTED. A future approved branding-storage milestone must replace generic Laravel shell branding with one server-resolved University/College branding context. University Profile and Affiliated College Branding are the respective upload sources, and the resolved logo must be reused consistently across approved UI, report, receipt, print and document surfaces. This does not alter the next eligible milestone.

## Update Rule
After every `next` milestone:
1. update verified statuses;
2. record useful migration/API/page references if needed;
3. identify the next eligible milestone;
4. do not mark later milestones implemented.

## Course / Subject Master — implemented 2026-08-21

- Course Categories: IMPLEMENTED.
- Course Types: IMPLEMENTED.
- Course / Subject Master: IMPLEMENTED.
- Current academic milestone: Curriculum Header.
- Current Curriculum Manage Structure milestone: Terms / Semesters.
- Next eligible Curriculum milestone after Terms / Semesters validation: Curriculum Slots — Course Category, Slot Name and Display Order first.
- Course / Subject Master is University-scoped and reusable.
- Fields: Course Category, Course Type, Course / Subject Name, Course Code, Description, Display Order, Status.
- Controller authorization follows the ERP's custom `hasPermission()` pattern.
- University scope follows the existing Academic Master pattern using `University::firstOrFail()`.
- Create/update/status lifecycle uses `AcademicMasterService`.
- Course Master intentionally does not directly bind Program Template, Discipline, Specialization, semester/term, credits, or L-T-P/contact hours. Those belong to Curriculum / Course Mapping.

## Application Navigation Architecture — 2026-08-22
- Hierarchy-based collapsible sidebar: IMPLEMENTED IN REPLACEMENT PACKAGE
- Flat University/Admin master links regrouped into:
  - Institution Setup
  - User & Access Management
  - Academic Setup
  - College Management when College scope applies
- Academic Setup contains nested Degree Structure, Program Setup, and Course Setup branches.
- Navigation filtering remains driven by backend-issued effective permission codes.
- Parent branches are hidden automatically when no permitted child remains.
- Current Laravel route URLs remain unchanged.
- Active child routes automatically open their ancestor branches.
- Backend authorization remains authoritative; sidebar visibility is not an authorization boundary.
- Premium tree/accordion interaction: IMPLEMENTED IN REPLACEMENT PACKAGE
  - Child relationships use semantic tree connector lines.
  - Only one sibling branch at each depth remains open.
  - Current-route ancestors auto-open after navigation/refresh.
  - Expand/collapse uses restrained grid/opacity motion and chevron rotation with reduced-motion support.
  - Styling consumes semantic sidebar/theme tokens only and introduces no hard-coded theme colors.
  - No extra/future hierarchy modules were added.

- Curriculum sidebar path: `Academic Setup -> Curriculum -> Curriculum Header`.
- Access management sidebar label: `Access Management`.

- Terms / Semesters implementation rule: contextual to a selected Curriculum Header; DRAFT-only mutation; ACTIVE/RETIRED versions remain read-only; existing `curriculum.view/update` permissions reused.


## Curriculum Structure Update — 2026-08-22
- Curriculum Header: implemented.
- Terms / Semesters: implemented.
- Curriculum Slots Phase 1: implemented with Course Category, Slot Name and Display Order.
- Slot-level Credits are not introduced.
- Course / Paper Mapping remains a later milestone.
- Copy / Clone Structure is approved as the future reuse pattern when a new Curriculum/version needs an existing structure; source and target records remain independent.


- Curriculum Slots Phase 2: implemented with Course Type, Mandatory/Choice, Minimum Selection and Maximum Selection.
- Choice selection counts are enforced only for Choice Slots.
- Mandatory Slots store no Min/Max selection counts.
- Next Curriculum milestone: Course / Paper Mapping.


## Course / Paper Mapping Update — 2026-08-22
- Course / Paper Mapping: IMPLEMENTED_IN_REPLACEMENT_PACKAGE — repository migration/build validation pending.
- Reuses existing Course / Subject Master.
- Enforces same University, ACTIVE Course, matching Course Category and matching Course Type.
- No Mapping Display Order or Credit in this milestone.
- Next Curriculum milestone: Mapping Display Order.


## Mapping Display Order Update — 2026-08-22
- Mapping Display Order: IMPLEMENTED_IN_REPLACEMENT_PACKAGE.
- New mappings receive the next Slot-relative order automatically.
- Reordering one mapping normalizes the selected Slot to continuous order values.
- Next work must respect the frozen sequence; Credit has still not been introduced.


## Discipline/Specialization-Aware Mapping — 2026-08-22
Course / Paper Mapping now assigns required Program Template Discipline and optional Specialization context while keeping Course Master independent.


## Course Mapping Edit — 2026-08-22
Existing Course / Paper Mapping is editable while Curriculum is DRAFT. Discipline, optional Specialization and Course / Subject can be corrected. ACTIVE/RETIRED remain read-only.


## Curriculum Validation Phase 1 — 2026-08-22
Implemented read-only non-credit Curriculum Structure Validation for Terms, Slots, Choice rules, Course Mappings, ordering, Program Template Discipline and optional Specialization context. Credit validation remains deferred.


## Validation Phase 1 Fix — 2026-08-22
Validation UI compile blocker fixed. Validate Structure is available independently of edit lifecycle, while Map/Edit remain lifecycle-protected. Validator schema names now match implemented Terms and Slots.


## Curriculum Validation Placement — 2026-08-22
Validate Structure is now exposed at Curriculum Manage Structure level, not inside a Slot's Course Mapping page. Scope remains the complete Curriculum.


## Curriculum Slot Credits — 2026-08-22
- Slot Credit Binding: IMPLEMENTED_IN_REPLACEMENT_PACKAGE.
- Credits are stored on `curriculum_slots`.
- Course Master remains reusable and does not receive curriculum-specific Credits.
- Course Mapping does not duplicate Credits.
- Phase 1 non-credit validation remains available as an interim structural diagnostic.
- Next: Credit Summary, then final credit-aware validation, then Copy / Clone Structure.


## Blank Screen UI Fix — 2026-08-22
Curriculum Manage Structure blank-screen regression fixed by restoring the missing `ShieldCheck` import used by the Curriculum-level Validate Structure button. No domain behavior changed.


## Credit Summary — 2026-08-22
- Credit Summary: IMPLEMENTED_IN_REPLACEMENT_PACKAGE.
- Derived from active Curriculum Slots; no duplicate summary table.
- Mandatory Slot contributes one Slot Credit.
- Choice Slot Required = Slot Credit × Min Selection.
- Choice Slot Maximum = Slot Credit × Max Selection.
- Next: final Credit-aware Curriculum Validation.


## Final Credit-aware Curriculum Validation — 2026-08-22
- Final Credit-aware Validation: IMPLEMENTED_IN_REPLACEMENT_PACKAGE.
- Existing structural validation remains active.
- Active Slots are checked for missing/invalid Credits.
- Credit Summary remains derived and read-only.
- Next: Copy / Clone Structure.


## Copy / Clone Structure — 2026-08-22
- Clone Entire Curriculum Structure: IMPLEMENTED_IN_REPLACEMENT_PACKAGE.
- Clone Semester: IMPLEMENTED_IN_REPLACEMENT_PACKAGE.
- Clone Slot: IMPLEMENTED_IN_REPLACEMENT_PACKAGE.
- All clones create independent Terms/Slots/Mappings with new IDs.
- Complete clone includes Slot Credits and Course Mapping academic context.


## Curriculum Delete Safety — 2026-08-22
- Fixed CurriculumTerm `slots()` Eloquent relationship required by Slots page.
- DRAFT Term delete: implemented.
- DRAFT Slot delete: implemented.
- DRAFT Course Mapping delete: implemented.
- Future Student Group assignment lock is mandatory and centralized/documented.


## Cross-Version Partial Clone — 2026-08-22
- Clone Semester supports same or another compatible DRAFT Curriculum version.
- Clone Slot supports same or another compatible DRAFT Curriculum version.
- Semester target does not need a Semester beforehand; clone creates it.
- Slot target requires an existing Semester.
- Target compatibility: same University + same Program Template + DRAFT.
- Source may be DRAFT/ACTIVE/RETIRED.


## Entire Curriculum Safe Delete — 2026-08-22
- Entire DRAFT Curriculum delete: IMPLEMENTED_IN_REPLACEMENT_PACKAGE.
- Deletes complete owned hierarchy: Curriculum -> Terms -> Slots -> Course/Paper Mappings.
- UI requires typing the exact Curriculum Code.
- Backend blocks delete unless lifecycle is DRAFT.
- Student Group/Cohort assignment lock remains a mandatory guard to connect when the canonical assignment module/table is implemented.
- Audit event: `CURRICULUM_DELETED`.


## Phase 5 — Governance Approval Workflow — 2026-08-22
Status: IN PROGRESS
- Phase 5A Workflow Setup implemented.
- Generic approval_workflows + ordered approval_workflow_stages added.
- Approver is Role-based from existing Role Master; no Dean/Director/Registrar names are hard-coded.
- Curriculum is the first supported `applies_to`.
- Curriculum submit/inbox/decision/publish is Phase 5B and is NOT yet implemented.


## Phase 5B Academic Approval Execution — 2026-08-22
- Curriculum Submit for Approval: IMPLEMENTED_IN_REPLACEMENT_PACKAGE.
- Validate Structure is mandatory before submission.
- Workflow stages are snapshotted into approval request stages.
- Curriculum locks while approval is pending.
- Approval Inbox resolves current stage by active/effective Role assignment.
- Approve advances level; final approval activates Curriculum.
- Return/Reject unlock DRAFT Curriculum for correction and resubmission.
- Approval history is displayed.
