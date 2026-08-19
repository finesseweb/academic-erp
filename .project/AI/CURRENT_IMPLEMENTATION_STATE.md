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
  - Permission: sensitive `scope.update`
  - University/active-College scope validation, status/effective dates, duplicate/system/self safeguards, transactional audit, and runtime effective-period permission enforcement implemented
  - Focused verification: 7 tests / 43 assertions; TypeScript, ESLint and Prettier passed
  - Review: PENDING_REVIEW
- Audit Logs: VERIFY_REPOSITORY

### University Academic Setup
- Academic Sessions: VERIFY_REPOSITORY
- Degree Levels: VERIFY_REPOSITORY
- Degrees: VERIFY_REPOSITORY
- Disciplines / Specializations: VERIFY_REPOSITORY
- Program Templates: VERIFY_REPOSITORY
- Course Categories: VERIFY_REPOSITORY
- Course Types: VERIFY_REPOSITORY
- Course / Paper Master: VERIFY_REPOSITORY
- Curriculum Header: VERIFY_REPOSITORY
- Curriculum Manage Structure:
  - Terms / Semesters: VERIFY_REPOSITORY
  - Slot Category / Slot Name / Display Order: VERIFY_REPOSITORY
  - Slot Course Type / Credit / Selection Rules: VERIFY_REPOSITORY
  - Course Mapping: VERIFY_REPOSITORY
  - Mapping Display Order: VERIFY_REPOSITORY
  - Credit Summary: VERIFY_REPOSITORY
  - Structure Validation: VERIFY_REPOSITORY

### Later Modules
Remain planned unless repository inspection proves otherwise.

## Next Eligible Milestone

Audit Logs is the next eligible milestone in the frozen hierarchy. Existing repository artifacts remain `VERIFY_REPOSITORY` and must be reconciled against its page specification before implementation.

## Approved Deferred Cross-Cutting Standards

- Institution branding: PLANNED/NOT IMPLEMENTED. A future approved branding-storage milestone must replace generic Laravel shell branding with one server-resolved University/College branding context. University Profile and Affiliated College Branding are the respective upload sources, and the resolved logo must be reused consistently across approved UI, report, receipt, print and document surfaces. This does not alter the next eligible milestone.

## Update Rule
After every `next` milestone:
1. update verified statuses;
2. record useful migration/API/page references if needed;
3. identify the next eligible milestone;
4. do not mark later milestones implemented.
