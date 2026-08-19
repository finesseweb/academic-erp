# Role Permission Matrix Page

Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW


## Identity
- Module: Role & Permission Management
- Route: `/super-admin/roles/:id/permissions`
- Permissions: `role.view`, `permission.view`, `permission.assign_to_role`, `permission.remove_from_role`
- Delivery phase: SA-04

## Purpose
Configure a role as a bundle of granular permissions.

## Premium UI Requirements
- sticky role summary header
- permission search
- module/resource grouped sections
- expand/collapse
- per-module select/clear
- individual permission checkboxes
- sensitive permission badge for approve/publish/refund/security operations
- selected-count indicator
- unsaved-change indicator
- effective change summary before save when changes are substantial

## API
- `GET /admin/roles/:id`
- `GET /admin/permissions`
- `GET /admin/roles/:id/permissions`
- `PUT /admin/roles/:id/permissions`

## Backend Rules
Laravel validates every permission ID/code. The frontend cannot create hidden/unregistered permissions by submitting arbitrary values.

## Audit
`ROLE_PERMISSIONS_UPDATED`, including added/removed permission codes.

## Realtime
Not required.
