# Curriculum Header

## Status
Implementation: IMPLEMENTED_IN_REPLACEMENT_PACKAGE  
Repository validation: REQUIRED_AFTER_COPY

## Purpose
Curriculum Header is the first milestone of Curriculum. It creates the University-owned,
versioned curriculum identity for one active Program Template and one active Academic Session.

This page does not contain terms, semesters, slots, course mapping or credit totals.
Those remain in the next Curriculum Manage Structure milestone.

## Navigation
`Academic Setup -> Curriculum -> Curriculum Header`

## Route
`GET /admin/curricula`

## Fields
- Program Template — required; active and owned by the same University.
- Academic Session — required; active and owned by the same University.
- Curriculum Code — required; unique within University.
- Curriculum Name — required.
- Version — required.
- Effective From — optional.
- Effective To — optional; cannot precede Effective From.
- Lifecycle Status — `DRAFT`, `ACTIVE`, `RETIRED`.
- Description — optional.

## Permissions
- `curriculum.view`
- `curriculum.create`
- `curriculum.update`
- `curriculum.disable`

Controller and Form Request authorization must use the ERP's established
`hasPermission()` mechanism. Laravel Gate `can()` is not the authorization contract for
this repository.

Curriculum permissions are University-scoped, are not College-delegable and are initially
granted to the protected global `SUPER_ADMIN` role when the implementation migration runs.

## Lifecycle
- No hard delete.
- `curriculum.disable` retires a Curriculum Header by changing lifecycle to `RETIRED`.
- Later structure-locking/activation rules are deferred until Manage Structure exists.

## Audit
- `CURRICULUM_CREATED`
- `CURRICULUM_UPDATED`
- `CURRICULUM_RETIRED`

Audit actor uses the existing immutable `audit_logs.actor_user_id` relationship.

## Explicitly Deferred
- Terms / Semesters
- Curriculum Slots
- Course / Paper Mapping
- Semester credit totals
- Curriculum total credits
- Structure validation

## Manage Structure Entry
Each Curriculum Header row exposes a `Manage Structure` action.

Current implemented child:
- Terms / Semesters

The action opens the selected Curriculum Header context at:
`/admin/curricula/{curriculum}/structure/terms`

Future Curriculum Structure children are not shown until their approved milestone.

## Table / Action UI Reference
Curriculum Header uses the Permission Catalog table language for header background, row spacing, hover state and status pills. Actions remain compact icon + text controls: `Structure`, `Edit`, `Retire` or `Restore`.
