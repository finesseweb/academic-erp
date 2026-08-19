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
