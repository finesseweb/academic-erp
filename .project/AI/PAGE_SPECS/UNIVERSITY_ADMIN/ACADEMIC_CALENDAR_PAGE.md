# Academic Calendar Page

## Status
- Documentation: APPROVED
- Implementation: CORE_IMPLEMENTED; PENDING_REVIEW
- Review: PENDING_REVIEW
- Implemented: 2026-08-25

## Route / Page
- Route: `GET /admin/academic-calendars`
- React page: `resources/js/pages/academic-calendars/index.tsx`
- Page type: University academic configuration + event management.

## Purpose
Manage one official University Academic Calendar per Academic Session and its dated events. This establishes the authoritative University calendar that future College Academic Calendars must adopt except where an individual University event explicitly permits College override.

## Scope Boundary
Included:
- Academic Session calendar header.
- University academic events/date ranges.
- registration periods, instruction milestones, examination windows, holidays, vacations and other University calendar dates.
- per-event `allow_college_override` governance flag.

Excluded:
- Faculty/class Routine or Timetable.
- Student Attendance execution.
- Assessment Scheme configuration.
- detailed Examination Timetable / paper scheduling.
- College override execution (future College Academic Setup milestone).

## Permissions
- `academic_calendar.view`
- `academic_calendar.create`
- `academic_calendar.update`
- `academic_calendar.disable`
- `academic_calendar.event_create`
- `academic_calendar.event_update`
- `academic_calendar.event_disable`

All are University-scoped, non-College-delegable, and initially granted to protected `SUPER_ADMIN` by migration.

## Header Rules
- Exactly one University Academic Calendar per Academic Session.
- Calendar code is unique within University.
- Calendar Academic Session cannot be changed after creation; create a calendar against the correct session.
- Header lifecycle is non-destructive `ACTIVE | INACTIVE`.

## Event Rules
- `start_date <= end_date`.
- Both dates must remain within the parent Academic Session.
- Event type is controlled by the implemented application list while physical storage remains a bounded string for future-safe additions without an ENUM migration.
- College override is opt-in per event and defaults to false.
- Inactive events remain in history and are not physically deleted.
- If the parent Academic Session dates are later moved/shortened, the Session update is blocked when any existing Calendar event would fall outside the proposed new range. Events are never silently disabled or deleted.

## UX
- Page header consistent with Academic Sessions.
- Calendar cards show session/current/status context.
- Create/edit calendar uses Dialog + Inertia Form.
- Events are displayed in a responsive table under their calendar.
- Shared DatePicker is mandatory for event dates.
- Empty states, inline Laravel validation errors, processing state/spinner and permission-aware actions are mandatory.

## Audit
Create/update/status changes for calendar headers and events write to the existing `audit_logs` architecture with University scope.
- Calendar lifecycle events are explicit: `ACADEMIC_CALENDAR_ACTIVATED` / `ACADEMIC_CALENDAR_DEACTIVATED`.
- Calendar Event lifecycle events are explicit: `ACADEMIC_CALENDAR_EVENT_ACTIVATED` / `ACADEMIC_CALENDAR_EVENT_DEACTIVATED`.

## Definition of Done / QA
1. Migration creates calendar/event tables and permissions.
2. Super Admin can open page.
3. One calendar can be created for an Academic Session.
4. Duplicate calendar for same session is rejected.
5. Event inside session dates can be created/updated.
6. Event outside session dates is rejected.
7. College override flag persists and displays correctly.
8. Calendar/event enable-disable is audited and non-destructive.
9. Unauthorized users are rejected by backend permission checks.
10. Parent Academic Session date reduction is rejected when existing Calendar events would become out-of-range.
11. Audit Log clearly distinguishes activation from deactivation for Calendar and Calendar Event.
12. Page works on desktop/tablet/mobile and build passes.

## Current Session Default — 2026-08-25
For `Add Academic Calendar`, the University's `ACTIVE + is_current` Academic Session is preselected when available.
Only ACTIVE Sessions are creation candidates. Current is a convenience/default, not a migration rule.
Existing Calendars remain permanently linked to their stored Academic Session.

## Curriculum Academic Periods - Implemented 2026-09-09, synchronized 2026-09-12

The University Academic Calendar can assign start/end dates to Terms/Semesters
from current approved Curricula in the Calendar's Academic Session.

- Selectable values are ACTIVE `curriculum_terms` belonging to current approved Curriculum versions for the same University and Academic Session.
- Superseded, unapproved, inactive, cross-University, and cross-Session Curricula/Terms are rejected by the backend.
- One Calendar may contain only one Academic Period per Curriculum Term.
- Period dates must remain inside both the Academic Session and the Curriculum effective date window.
- ACTIVE periods belonging to the same Curriculum cannot overlap.
- Each period stores `allow_college_override` and an `ACTIVE|INACTIVE` lifecycle.
- Create/update uses the existing `academic_calendar.event_update` permission.
- Changes are audited as `ACADEMIC_CALENDAR_TERM_PERIOD_CREATED` or `ACADEMIC_CALENDAR_TERM_PERIOD_UPDATED`.
- A Calendar Event may be University-wide or linked to one ACTIVE Academic Period. A linked event's dates must remain within that period.

Routes:
- `POST /admin/academic-calendars/{academicCalendar}/term-periods`
- `PATCH /admin/academic-calendars/{academicCalendar}/term-periods/{period}`

Storage: `academic_calendar_term_periods` and nullable
`academic_calendar_events.academic_calendar_term_period_id`.
