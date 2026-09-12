# Course / Subject Master Page

## Status
IMPLEMENTED — 2026-08-21

## Route
`/admin/courses`

## Purpose
Maintain reusable University-level Course / Subject definitions.

## Fields
- Course Category — required.
- Course Type — required.
- Course / Subject Name — required.
- Course Code — required.
- Display Order.
- Status.
- Description.

## Permissions
- `course.view`
- `course.create`
- `course.update`
- `course.disable`

## Authorization
Uses the existing ERP custom permission mechanism:

`$request->user()->hasPermission('course.<action>')`

The Course Controller follows the same University-scoped Academic Master pattern with `University::firstOrFail()` and uses `AcademicMasterService` for create/update/status lifecycle operations.

## UI consistency
Uses the existing `AcademicMasterPage` and existing semantic theme system. No separate CSS is introduced.

## Scope boundary
Program Template, Discipline, optional Specialization, semester/term, credits and L-T-P/contact hours are intentionally deferred to Curriculum / Course Mapping.
