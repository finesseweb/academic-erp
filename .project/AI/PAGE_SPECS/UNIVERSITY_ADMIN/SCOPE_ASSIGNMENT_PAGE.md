# Scope Assignment Page

Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW

## Identity

- Module: Access & Security / RBAC
- UI route: `/admin/users/:user/roles` (shared User Access page)
- Mutation route: `PATCH /admin/users/:user/roles/:assignment/scope`
- Permissions: `user.view` + `role.view` to view; sensitive `scope.update` to mutate
- Delivery phase: University Access & Security — Scope Assignment

## Purpose

Manage the explicit authorization boundary and lifecycle of an existing user-role assignment without duplicating the User Role Assignment screen.

## Supported Scope

- `UNIVERSITY` + `university`
- `COLLEGE` + `college:<id>` for an existing active affiliated College

Missing or arbitrary scope values never imply University/global access. Narrower Faculty, Department, Program, Course/Class and own-record scopes remain deferred until their domain entities and ordered milestones exist.

## UI / UX

- Each non-system assignment exposes a permission-aware **Edit scope** action.
- The dialog uses controlled University/College selection, an active affiliated-College dropdown, Active/Inactive lifecycle control, and the shared theme-aware date pickers.
- Current scope, status and optional effective period are visible on the assignment card.
- Mutation disables while processing, displays a spinner and pending copy, preserves scroll, uses inline validation, and returns a shared success toast.
- The UI uses semantic tokens, accessible labels/focus behavior, responsive layout and all four built-in themes.

## Backend Rules

- Laravel requires `scope.update`; React visibility is not authorization.
- The assignment must belong to the user in the route.
- Protected system assignments and the acting administrator's own assignments cannot be changed through this workflow.
- College IDs are resolved by Laravel and must reference an active College; clients cannot submit a canonical reference directly.
- The assignment's role/scope combination must remain unique.
- Status is explicitly `ACTIVE` or `INACTIVE`; the optional end date cannot precede the optional start date.
- Date-only form values are stored as inclusive timestamps: start of the selected first day and end of the selected final day.
- Effective permission resolution requires an active role, active assignment, matching scope, started/not-expired period, and the requested permission on that same role assignment.

## Audit

`USER_ROLE_SCOPE_UPDATED` records safe before/after role code, canonical scope, lifecycle status and effective timestamps in the same transaction as the update.

## Realtime

Standard Inertia request/response only. No WebSocket behavior is justified.

## Verification

- Permission denial, active-College validation, date validation, duplicate prevention, system/self protections, audit persistence and effective-period authorization have feature coverage.
- TypeScript, ESLint, Prettier, Laravel tests and production build are required.

## Change History

- 2026-08-20: Implemented Scope Assignment on the shared User Access page.
