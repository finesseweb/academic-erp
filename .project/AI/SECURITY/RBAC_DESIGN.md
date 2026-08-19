# RBAC Design

## Purpose
Define the authorization model for the multi-college Academic ERP.

## Core Model
The ERP uses **Role-Based Access Control (RBAC) with granular permissions and explicit scope**.

`User -> User Role Assignment -> Role -> Role Permissions -> Permission`

Each assignment also has an authorization scope.

A successful authorization decision requires all applicable checks:
1. User is authenticated and active.
2. User has an active role assignment.
3. The role grants the required permission.
4. The assignment covers the requested tenant/resource scope.
5. Resource ownership/assignment rules pass.
6. Workflow/business-state rules pass.

## Principles
- Roles group permissions; roles are not the primary checks in application code.
- Permissions describe actions on business resources.
- Scope limits where a granted permission applies.
- Backend authorization is mandatory.
- Frontend authorization improves UX but never replaces backend enforcement.
- Deny by default when a permission or scope cannot be proven.
- Never infer cross-college access from a user's ability to log in.

## System Roles and Custom Roles
System role templates may provide sensible defaults such as Super Admin, College Admin, Faculty and Student.

Custom roles should be supported so an University or authorized College can create roles such as:
- Admission Operator
- Fee Collection Clerk
- HOD
- Examination Data Entry Operator
- Result Verifier

Custom roles must use the same permission catalog and scope rules as system roles.

## Proposed Authorization Entities
Before creating or modifying tables, inspect the real legacy MySQL schema and Laravel migrations / Eloquent schema. Reuse existing entities where they correctly represent these concepts.

Required business concepts are:
- User
- Role
- Permission
- User-to-role assignment
- Role-to-permission assignment
- Authorization scope
- Audit event

A possible normalized target model is:

### roles
- `id`
- `college_id` nullable for global/system roles where appropriate
- `name`
- `code`
- `is_system_role`
- `status`
- timestamps

### permissions
- `id`
- `module`
- `resource`
- `action`
- `code` unique
- description/status
- timestamps

### role_permissions
- `role_id`
- `permission_id`
- unique (`role_id`, `permission_id`)

### user_roles
- `id`
- `user_id`
- `role_id`
- scope fields or reference to an approved scope model
- status/effective dates where required
- timestamps

The initial physical implementation is approved by migration `20260813091728_core_identity_rbac_foundation`: `users`, `roles`, `permissions`, `role_permissions`, `user_roles`, `user_sessions` and `audit_logs`. Laravel migrations / Eloquent and the table specifications are authoritative for exact columns.

`scope_type` plus canonical `scope_reference` makes assignments extensible without adding nullable columns for every future domain. Current global scope is `GLOBAL` + `global`; Affiliated College scope will use `COLLEGE` + `college:<stable-id>` after the College entity exists. Services must validate canonical scope references and resource ownership. Scope references are not foreign keys and must never be accepted from clients as proof of authorization.

## Scope Model
Scope should be as narrow as the business assignment requires.

Examples:
- Super Admin: global scope.
- College Admin: one college.
- Department Admin/HOD: one college + one or more departments.
- Faculty: one college + assigned courses/classes/offerings.
- Student: own student identity/records only.

Avoid encoding every possible scope dimension as nullable columns without first verifying actual ERP relationships. Prefer a model that follows the real academic ownership graph.

## Permission Naming
Use stable lowercase codes:

`resource.action`

Avoid UI-oriented names such as `student_page_access` and broad names such as `everything_manage`.

Permissions should represent business capabilities and survive UI redesigns.

## Multiple Roles
A user may have multiple active role assignments if required by the business. Effective permissions are the union of granted permissions **within each assignment's valid scope**.

A permission from College A must never be combined with the scope of a different assignment in College B.

## Revocation
Role/permission revocation must take effect predictably. If effective permissions are cached or embedded in JWT claims, the implementation must define token lifetime/versioning/invalidation so removed access is not retained indefinitely.

## No Negative Permissions by Default
Prefer additive grants and deny-by-default. Do not introduce per-user deny overrides unless a concrete requirement proves they are necessary; they make authorization reasoning substantially harder.

## Canonical University Scope
Authorization scope must support `University -> College -> Faculty/School -> Department -> Program -> Course/Class -> Own Record / Linked Child`.

Examples:
- Super Admin: University-wide.
- College Admin: assigned College.
- HOD: assigned Department.
- Faculty: assigned Course/Class.
- Student: own record.
- Parent: linked child/children.

Finance permissions also obey University-vs-College governance in `../DOMAIN/FEE_GOVERNANCE_AND_INSTALLMENTS.md`.
