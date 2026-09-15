# Curriculum Manage Structure — Terms / Semesters

## Status
Implementation: IMPLEMENTED_IN_REPLACEMENT_PACKAGE  
Repository validation: REQUIRED_AFTER_COPY

## Purpose
Defines the ordered Terms / Semesters for one existing Curriculum Header.

The page is contextual to a specific Curriculum Header and is opened from that header's
`Manage Structure` action.

## Navigation
`Academic Setup -> Curriculum -> Curriculum Header -> Manage Structure -> Terms / Semesters`

The sidebar remains:
`Academic Setup -> Curriculum -> Curriculum Header`

A static `Terms / Semesters` sidebar link is intentionally not added because Terms / Semesters
cannot be managed without first selecting a Curriculum Header.

## Route
`GET /admin/curricula/{curriculum}/structure/terms`

## Fields
- Sequence — required positive integer, unique within the Curriculum Header
- Term / Semester Name — required curriculum-specific label
- Status — `ACTIVE|INACTIVE`

## Authorization
- View: existing `curriculum.view`
- Create/update/status: existing `curriculum.update`
- Use the ERP custom `hasPermission()` mechanism
- No new permission family is introduced for this child milestone

## Lifecycle / Historical Protection
- A `DRAFT` Curriculum Header may change Terms / Semesters.
- An `ACTIVE` or `RETIRED` Curriculum Header is read-only.
- No hard delete is provided.
- This enforces the frozen rule that Curriculum versions preserve historical structures.

## Explicitly Not Included
- Curriculum Slots
- Course Category assignment
- Slot Name
- Course Type
- Credit
- Mandatory / Choice
- Minimum / Maximum selection
- Course / Paper Mapping
- Credit totals
- Structure Validation
- Academic Calendar dates

Those remain later milestones in the frozen Curriculum sequence.

## Table / Action UI Reference
Terms / Semesters uses the Permission Catalog table language. `Active` uses the standard emerald status pill; `Inactive` uses the standard muted pill. Actions use Pencil + `Edit` and Circle-X/Rotate-Ccw + `Set Inactive`/`Set Active`.

## Next Child Navigation
Each Term / Semester row exposes `Slots`, opening the selected term context at:
`/admin/curricula/{curriculum}/structure/terms/{term}/slots`

Slot Phase 1 contains only Course Category, Slot Name and Display Order.
