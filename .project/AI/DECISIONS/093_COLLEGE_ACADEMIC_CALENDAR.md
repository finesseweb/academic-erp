# ADR 093 — College Academic Calendar

## Status
IMPLEMENTED — OWNER QA REQUIRED — 2026-09-04

## Context
The University Academic Calendar is already authoritative per Academic Session. Each University event independently declares whether a College may override it through `allow_college_override`.

College Academic Setup is now complete through Batch and Section. Before Student Enrollment / teaching-delivery modules depend on dates, each College needs an operational Academic Calendar that remains governed by the University source rather than creating a disconnected duplicate calendar.

## Decision
1. A College adopts one existing University Academic Calendar through `college_academic_calendars`.
2. The College calendar references `academic_calendars.id` through `university_academic_calendar_id`; Academic Session and University event definitions are inherited, not duplicated.
3. A College calendar starts INACTIVE and may be activated only while the College and parent University Academic Calendar are ACTIVE.
4. University events remain visible and authoritative in the College Calendar.
5. A College may override an event only when the exact University event is ACTIVE and `allow_college_override = true`.
6. Overrides are stored in `college_calendar_overrides` and retain the exact University Calendar + Event references, College replacement title/dates/description, mandatory reason, status and audit actors.
7. Override dates must remain inside the parent Academic Session.
8. Disabling an override does not alter the University event; the University event becomes effective again.
9. College Calendar configuration does not alter Batch, Section, Intake, Reservation, Admission or seat-capacity data.
10. No College-only free-standing event type is introduced in this milestone. The implemented scope is University-calendar adoption plus governed event override. A later local-event requirement must be a separate documented decision rather than bypassing University governance.

## RBAC
College-delegable permissions:
- `college_academic_calendar.view`
- `college_academic_calendar.create`
- `college_academic_calendar.update`
- `college_academic_calendar.enable`
- `college_academic_calendar.disable`
- `college_academic_calendar.override_create`
- `college_academic_calendar.override_update`
- `college_academic_calendar.override_disable`

Default protected grants: `SUPER_ADMIN`, `COLLEGE_ADMIN`.

## Audit
- `COLLEGE_ACADEMIC_CALENDAR_ADOPTED`
- `COLLEGE_ACADEMIC_CALENDAR_UPDATED`
- `COLLEGE_ACADEMIC_CALENDAR_ACTIVATED`
- `COLLEGE_ACADEMIC_CALENDAR_DEACTIVATED`
- `COLLEGE_CALENDAR_OVERRIDE_CREATED`
- `COLLEGE_CALENDAR_OVERRIDE_UPDATED`
- `COLLEGE_CALENDAR_OVERRIDE_ACTIVATED`
- `COLLEGE_CALENDAR_OVERRIDE_DEACTIVATED`

## Next
Owner QA -> Student Enrollment / Lifecycle.
