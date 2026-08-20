# College Scope Assignment
Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW

## Identity
- Mutation: `PATCH /college/:college/users/:user/roles/:assignment/scope`
- Permission: `college_scope.update` at the same College scope

## Rules
- College administrators cannot transfer an assignment to another College. The canonical College scope is fixed from the authorized route context.
- The assignment, target user and custom role must all belong to the same College.
- Administrators may update Active/Inactive status and optional effective-from/effective-until dates; end date must not precede start date.
- Self-lifecycle changes and cross-College URL tampering are rejected.
- Runtime RBAC ignores inactive, future and expired grants.
- Changes use the shared date picker and transactional `USER_ROLE_SCOPE_UPDATED` audit event.

## Change History
- 2026-08-20: Implemented fixed-College assignment lifecycle and effective dates.
