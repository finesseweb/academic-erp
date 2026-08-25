# Permission Catalog

## Purpose
This is the canonical registry for ERP authorization permission codes.

## Rules
- Permission format: `resource.action`.
- Codes are stable API/security identifiers, not display labels.
- New permissions must be added here before or with implementation.
- Do not silently reuse a permission for a materially different capability.
- Prefer specific sensitive actions (`approve`, `publish`, `refund`) over broad `manage` permissions.
- Every PAGE_SPEC must reference the permissions required by its page/API actions.

## Authentication / Account
- `profile.view_own`
- `profile.update_own`
- `password.change_own`

## Platform Dashboard
- `platform.dashboard.view`

## User Administration
- `user.view`
- `user.create`
- `user.update`
- `user.disable`
- `user.enable`
- `user.reset_password`

## Role & Permission Administration
- `role.view`
- `role.create`
- `role.update`
- `role.disable`
- `role.assign`
- `role.unassign`
- `scope.update`
- `permission.view`
- `permission.assign_to_role`
- `permission.remove_from_role`

## Audit / Security
- `audit.view`

## College Access Administration
- `college_admin.assign`
- `college_user.view`
- `college_user.create`
- `college_user.update`
- `college_user.disable`
- `college_user.enable`
- `college_user.reset_password`
- `college_role.view`
- `college_role.create`
- `college_role.update`
- `college_role.disable`
- `college_permission.assign`
- `college_permission.remove`
- `college_role.assign`
- `college_role.unassign`
- `college_scope.update`

## Appearance / Theme Management
- `theme.view`
- `theme.select_own`
- `theme.manage_personal_selection`
- `theme.create`
- `theme.update`
- `theme.disable`
- `theme.set_global_default`
- `theme.set_institution_default`

## University / Affiliated College
- `university.view`
- `university.update`
- `college.view`
- `college.create`
- `college.update`
- `college.disable`
- `authorized_signatory.view`
- `authorized_signatory.create`
- `authorized_signatory.update`
- `authorized_signatory.disable`

## Student
- `student.view`
- `student.create`
- `student.update`
- `student.disable`
- `student.export`
- `student.document.view`
- `student.document.upload`

## Academic Masters
Initial examples; expand as modules are specified.
- `academic_master.view`
- `academic_master.create`
- `academic_master.update`

## Attendance
- `attendance.view`
- `attendance.mark`
- `attendance.edit`
- `attendance.report`
- `attendance.export`

## Fees / Accounts
- `fee.view`
- `fee.collect`
- `fee.receipt.print`
- `fee.discount.request`
- `fee.discount.approve`
- `fee.refund`
- `fee.report`
- `fee.export`

## Examination / Result
- `examination.view`
- `examination.schedule`
- `result.view`
- `result.enter`
- `result.edit`
- `result.verify`
- `result.publish`
- `result.report`
- `result.export`

## Library
- `library.view`
- `library.issue`
- `library.return`
- `library.catalog.manage`
- `library.report`

## Student Self-Service
Self-service permissions must always be combined with own-record scope.
- `student_portal.profile.view_own`
- `student_portal.attendance.view_own`
- `student_portal.fee.view_own`
- `student_portal.result.view_own`

## Parent Self-Service
Self-service permissions must always be combined with linked-child scope.
- `parent_portal.profile.view_own`
- `parent_portal.child.view`
- `parent_portal.child.attendance.view`
- `parent_portal.child.fee.view`
- `parent_portal.child.result.view`

## Catalog Status
The Core Identity/RBAC seed persists the Authentication/Account, Platform Dashboard, User Administration, Role & Permission Administration, Audit/Security, Appearance/Theme and University/Affiliated College permissions listed above. The `SUPER_ADMIN` system role receives those platform permissions at global scope. Personal theme choice requires `theme.select_own` plus an enabled applicable theme policy; `theme.manage_personal_selection` controls that policy and does not itself grant personal selection.

Academic, student, attendance, fee, examination, library and portal permissions remain documentation-only until their modules are implemented. A persisted permission means the authorization identifier is registered; it does not mean the related API or page is implemented.

Repository implementation note (2026-08-18): `university.view` and `university.update` are persisted and enforced for the University Profile at explicit `UNIVERSITY` / `university` scope. The remaining catalog entries must be verified or implemented only in their ordered milestones.

Repository implementation note (2026-08-19): `college.view`, `college.create`, `college.update`, and `college.disable` are persisted, granted to the protected Super Admin role, and enforced at explicit University scope for the Affiliated Colleges milestone.

Repository implementation note (2026-08-19): `authorized_signatory.view`, `authorized_signatory.create`, `authorized_signatory.update`, and `authorized_signatory.disable` are persisted, granted to the protected Super Admin role, and enforced at University scope for Authorized Signatories.

Repository implementation note (2026-08-19): `user.view`, `user.create`, `user.update`, `user.disable`, `user.enable`, and `user.reset_password` are persisted and enforced for administrative account management. Role and scope mutation is intentionally deferred.

Repository implementation note (2026-08-19): `role.view`, `role.create`, `role.update`, and `role.disable` are persisted and enforced for custom-role administration. System role identity/lifecycle is protected; permission assignment remains deferred.

Repository implementation note (2026-08-19): `permission.view` is persisted and enforced for the read-only implemented-capability catalog. The UI deliberately exposes no permission create, update, disable, or delete routes.

Repository implementation note (2026-08-20): `permission.assign_to_role` and `permission.remove_from_role` are persisted as sensitive capabilities and enforced independently from the submitted permission diff. System-role grants remain migration-controlled.

Repository implementation note (2026-08-20): `role.assign` and `role.unassign` are persisted as sensitive capabilities. Custom roles may be assigned at explicit University or validated College scope; protected system role assignments remain migration-controlled.

Repository implementation note (2026-08-20): sensitive `scope.update` is persisted and enforced when changing an existing assignment's canonical University/College scope, status or effective period. Protected system assignments and self-scope changes are blocked.

Repository implementation note (2026-08-21): sensitive, non-delegable `college_audit.view` is persisted for exact-College immutable access history. Academic Sessions persist `academic_session.view/create/update/close/set_current`; Degree Levels persist `degree_level.view/create/update/disable`. Academic permissions are University-scoped and initially granted to Super Admin.

Repository implementation note (2026-08-21): University Academic Setup persists and enforces `degree.*`, `discipline.*`, `program_template.*`, and `course_category.*` view/create/update/disable capability families. Disable is sensitive; these permissions are non-College-delegable and initially granted to Super Admin.

## University / College Governance
- `university.view`
- `university.update`
- `college.view`
- `college.create`
- `college.update`
- `college.disable`

## Expanded Fees / Installments
- `fee.head.view`
- `fee.head.create`
- `fee.head.update`
- `fee.policy.view`
- `fee.policy.create`
- `fee.policy.update`
- `fee.structure.view`
- `fee.structure.create`
- `fee.structure.update`
- `fee.structure.approve`
- `fee.installment_plan.view`
- `fee.installment_plan.create`
- `fee.installment_plan.update`
- `fee.installment_plan.approve`
- `fee.installment.assign`
- `fee.reschedule.request`
- `fee.reschedule.approve`
- `fee.waiver.request`
- `fee.waiver.approve`
- `fee.refund.request`
- `fee.refund.approve`

These permission identifiers are documentation-level until the corresponding backend capability is implemented.

## Course / Subject permissions — implemented 2026-08-21

- `course.view` — view Course / Subject Master and permit the sidebar entry.
- `course.create` — create Course / Subject records.
- `course.update` — update Course / Subject records.
- `course.disable` — change Course / Subject active/inactive status.

Controller authorization uses the project's custom `hasPermission()` mechanism.

## Curriculum Header
- `curriculum.view`
- `curriculum.create`
- `curriculum.update`
- `curriculum.disable`

Repository implementation note (2026-08-22): Curriculum Header persists and enforces the `curriculum.*` family at University scope. `curriculum.disable` retires the header rather than hard-deleting academic history. College delegation is disabled.

## Curriculum Header permissions — implemented 2026-08-22

- `curriculum.view` — view Curriculum Header and permit its sidebar leaf.
- `curriculum.create` — create Curriculum Headers.
- `curriculum.update` — update Curriculum Headers.
- `curriculum.disable` — retire Curriculum Headers; sensitive lifecycle action.

Controller/Form Request authorization uses the project's custom `hasPermission()` mechanism.
These permissions are University-scoped, non-College-delegable and initially granted to
the protected global `SUPER_ADMIN` system role by migration.


## Academic Approval Workflow permissions — Phase 5A implemented 2026-08-22
- `approval_workflow.view`
- `approval_workflow.create`
- `approval_workflow.update`
- `approval_workflow.disable`

Approver identities are never authorized by hard-coded role names. Workflow stages reference configured Role Master records.


## System Maintenance — Test Data Cleanup
- `test_data_cleanup.manage`
  - sensitive: yes
  - college delegable: no
  - purpose: view and execute controlled test-data cleanup tools
  - never grants raw SQL/database access

## Academic Calendar permissions — implemented 2026-08-25
- `academic_calendar.view`
- `academic_calendar.create`
- `academic_calendar.update`
- `academic_calendar.disable`
- `academic_calendar.event_create`
- `academic_calendar.event_update`
- `academic_calendar.event_disable`

These permissions govern the University Academic Calendar header and events. They are University-scoped, non-College-delegable and initially granted to protected `SUPER_ADMIN`. `disable` / `event_disable` are sensitive non-destructive lifecycle actions. Future College Academic Calendar permissions will be specified separately and must respect each University event's `allow_college_override` flag.

