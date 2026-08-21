# Course Types

## Status
Implementation: IMPLEMENTED. Review: PENDING_REVIEW. Implemented 2026-08-21.

## Purpose
University-owned controlled Course Type master used by the later Course / Subject Master and curriculum configuration. Course Type describes the academic/delivery nature of a course without introducing credits, L-T-P hours or curriculum rules at this stage.

## Fields
- Name — required, max 120 characters.
- Code — required, max 40 characters, unique inside the University.
- Description — optional, max 1000 characters.
- Display Order — required, 0–65535.
- Status — `ACTIVE` or `INACTIVE`.

## Behavior
- University-scoped list ordered by `display_order`, then `name`.
- Add/Edit uses the shared responsive `AcademicMasterPage` dialog and existing semantic theme tokens.
- Permission-aware sidebar navigation exposes `Course Types` immediately after `Course Categories` when the authenticated user has `course_type.view`.
- No separate Course Type CSS is introduced.
- Disable/Enable preserves existing references and controls availability for future configuration.
- Course Types are independent master records. Course / Subject Master will reference them later.

## Permissions
- `course_type.view`
- `course_type.create`
- `course_type.update`
- `course_type.disable`

`course_type.disable` is sensitive. Course Type permissions are non-College-delegable at this stage and initially granted to Super Admin by migration.

## Audit
Academic master service audit prefix: `COURSE_TYPE`.
Expected lifecycle events follow the existing academic-master convention, including create, update and status changes.

## Route
- `GET /admin/course-types`
- `POST /admin/course-types`
- `PATCH /admin/course-types/{courseType}`
- `PATCH /admin/course-types/{courseType}/status`
