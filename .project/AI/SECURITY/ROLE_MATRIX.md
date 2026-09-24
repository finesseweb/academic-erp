# Role Matrix

## Purpose
Define starting role templates and responsibility boundaries. The permission catalog remains the source of truth; this matrix is a default policy proposal to be validated against real college workflows.

| Role | Default Scope | Typical Capabilities | Explicit Boundary |
|---|---|---|---|
| SUPER_ADMIN | Global | Manage University and affiliated Colleges, global/system configuration, authorized user/role administration | Must not rely on client-supplied college scope |
| COLLEGE_ADMIN | Single college | Manage college users, masters and permitted modules | No other college access |
| DEPARTMENT_ADMIN | College + assigned department(s) | Department-level academic/student/faculty operations | No unrelated departments |
| FACULTY | College + assigned classes/courses | View assigned students, mark attendance, enter permitted academic data | No arbitrary course/department access |
| ACCOUNTANT | College, optionally assigned unit | Fee view/collection/receipt/report; approved finance operations | No academic/result administration unless separately granted |
| EXAMINATION_CONTROLLER | College/exam scope | Examination administration, result verification/publication | Sensitive publication must use explicit permissions |
| LIBRARIAN | College/library scope | Library catalog, issue/return/report operations | No unrelated ERP modules |
| STUDENT | Own record | Own profile, attendance, fees, results and approved self-service | Never another student's record |
| PARENT | Linked child/children | View approved information for linked children | Never access an unlinked student |

## Important Rule
Do not implement checks such as `if role == ADMIN then allow`. Use permissions and scope.

Example:
- An `ACCOUNTANT` role may normally have `fee.collect`.
- A custom `Fee Viewer` role may have only `fee.view` and `fee.report`.
- A senior finance role may additionally receive `fee.refund`.

No code change should be required merely to create a new combination of existing permissions.

## Suggested Sensitive Duty Separation
- Faculty/Data Entry: `result.enter`
- Verifier/HOD/authorized examiner: `result.verify`
- Examination Controller: `result.publish`
- Fee operator: `fee.collect`
- Authorized approver: `fee.discount.approve` and/or `fee.refund`

The exact role-to-permission seed data must be approved during implementation and documented in migrations/seeds.

## Implemented System Role Seed
- `SUPER_ADMIN` is seeded as a protected active system role owned by global scope.
- It is assigned every currently persisted platform permission from the Authentication, Platform, User Administration, RBAC, Audit, Appearance and University/College groups.
- The bootstrap Super Admin receives this role through an explicit `GLOBAL` + `global` user-role assignment.
- STUDENT, PARENT and other templates are not seeded until their approved modules require them; they fit the same role/permission/scope model.


## College Academic Setup defaults — 2026-09-04
`SUPER_ADMIN` and `COLLEGE_ADMIN` receive the implemented Batch and Section permissions through their registration migrations. Custom College roles receive only explicitly delegated `college_batch.*` / `college_section.*` permissions. No role-name shortcut is permitted.


## College Academic Calendar — 2026-09-04
Protected default role grants: `SUPER_ADMIN`, `COLLEGE_ADMIN`.

College Calendar permissions are College-scoped and delegable through the normal Role -> Permission -> scoped UserRole system. There is no hard-coded Calendar role bypass. University Academic Calendar permissions remain separate from College Calendar permissions.

## Fee Management defaults — 2026-09-04
- SUPER_ADMIN receives University + College Fee Management permissions.
- COLLEGE_ADMIN receives College Fee Management permissions only, always subject to exact College scope.
- University Fee ownership is not College-delegable.

### Fee Structure University inheritance / College adoption — 2026-09-04
- University roles with Fee Structure create/update permissions choose `MANDATORY` or `OPTIONAL` College Applicability on University-owned structures.
- College roles require `college_fee_structure.adopt` to Adopt / Stop Using an OPTIONAL University Fee Structure in their exact College scope.
- MANDATORY University Fee Structures are read-only and automatically effective for matching Colleges; no College permission may override or opt them out.

## ADR 197 — Fee Clearance default grants (2026-09-16)
- `SUPER_ADMIN`: `college_fee_clearance.view`
- `COLLEGE_ADMIN`: `college_fee_clearance.view`
- Custom College roles: may receive `college_fee_clearance.view` through the existing College-delegable permission-assignment flow.

The permission is read-only and non-sensitive. It does not grant any financial mutation capability.

### Student Enrollment — ENR-1
- `SUPER_ADMIN`: `college_student_enrollment.view`
- `COLLEGE_ADMIN`: `college_student_enrollment.view`
- Custom College roles: may receive `college_student_enrollment.view` through the existing College-delegable permission assignment flow.

### ENR-2 default protected grants
- `college_student_enrollment.enroll`: SUPER_ADMIN = granted; COLLEGE_ADMIN = granted. Other College staff require explicit delegated permission and remain College-scoped.

## Course Delivery defaults (2026-09-20)
`SUPER_ADMIN` and `COLLEGE_ADMIN` receive the four `college_course_offering.*` permissions by migration. Other College roles receive no automatic Course Offering access and may receive College-delegable permissions through the existing role/permission workflow.
## Faculty Allocation defaults (2026-09-21)

SUPER_ADMIN and COLLEGE_ADMIN receive the five management permissions. `college_faculty_allocation.eligible` is deliberately not granted by default; College administrators grant it to their Faculty role. Allocation-management permission does not make a user teaching faculty.
## Course Delivery scheduling defaults (2026-09-21)

SUPER_ADMIN and COLLEGE_ADMIN receive Room, Timetable and Class Scheduling permissions. Other roles receive them only through College role permission assignment.
# ADR 214 Attendance protected-role grants — 2026-09-24

The forward migration grants the five Attendance exception/eligibility permissions to protected `SUPER_ADMIN` and `COLLEGE_ADMIN` roles. College-owned roles may receive them through the existing delegation controls; backend College scope remains mandatory.

## ADR 215 Internal Assessment grants — 2026-09-24

Protected `SUPER_ADMIN` and `COLLEGE_ADMIN` receive view/setup/assignment/quiz permissions. Authorized College roles such as Faculty may be delegated view and the appropriate activity capability. Faculty Allocation and server-side College ownership remain separate mandatory domain checks.

ADR 216 grants protected `SUPER_ADMIN` and `COLLEGE_ADMIN` Mid Semester, Practical and Marks Entry permissions. College roles may receive delegated capabilities; Marks Entry remains sensitive and audited.
