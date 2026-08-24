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
- DRAFT setup records may be hard-deleted only through the implemented guarded delete path before operational assignment.
- `curriculum.disable` retires an eligible Curriculum Header by changing lifecycle to `RETIRED`.
- `ACTIVE / APPROVED` Curriculum data is read-only. Approved academic changes use `Amend Curriculum`, never direct edit.
- Amendment creates a linked DRAFT version in the same Program Template + Academic Session and copies the complete structure.
- After amendment approval, the new approved version is Current and the source approved version remains Previous history.

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
Curriculum Header uses the Permission Catalog table language for header background, row spacing, hover state and status pills. Actions remain compact icon + text controls. Primary actions remain `Structure` and eligible `Edit`; `More` contains lifecycle/governance actions including `Amend Curriculum`, `Clone Structure`, approval, delete/retire/restore as applicable.


## Amendment Rule — 2026-08-24
See `CURRICULUM_AMENDMENT_VERSIONING.md` and decision `007_CURRICULUM_AMENDMENT_VERSIONING.md`.

`Add Curriculum` creates an independent Curriculum header. `Amend Curriculum` creates the next linked version of an already approved Curriculum.
