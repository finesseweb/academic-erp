# User Role Assignment Page

Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW


## Identity
- Module: RBAC
- Route: `/super-admin/users/:id/access`
- Permissions: `user.view`, `role.view`, `role.assign`, `role.unassign`
- Delivery phase: SA-03

## Purpose
Assign one or more roles to a user with explicit scope.

## UI
- user summary
- current role assignments
- add assignment
- role selector
- scope selector: global/College and later narrower supported scopes
- effective permission preview
- remove assignment action

## Rules
Never infer global access from a missing client scope. Laravel validates whether the acting administrator may grant the requested role and scope.

## API
- `GET /admin/users/:id/roles`
- `PUT /admin/users/:id/roles`
- `PATCH /admin/users/:id/roles/:assignment/scope` (implemented by the ordered Scope Assignment milestone)

## Audit
`USER_ROLE_ASSIGNED`, `USER_ROLE_UNASSIGNED`, scope changes.

## Related Milestone

Existing-assignment scope, lifecycle status and effective-date editing is implemented by `SCOPE_ASSIGNMENT_PAGE.md` and reuses this page.
