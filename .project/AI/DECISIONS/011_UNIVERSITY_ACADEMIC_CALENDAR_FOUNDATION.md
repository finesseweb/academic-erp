# ADR 011 — University Academic Calendar Foundation

## Decision
The University Academic Calendar is modeled as one `academic_calendars` header per Academic Session plus normalized `academic_calendar_events` rows.

## Why
- Academic Session defines the overall academic period; the Academic Calendar defines governed dates/events inside that period.
- Events must remain reusable by future College Academic Calendar adoption/override logic.
- Routine/Timetable and detailed Examination scheduling are separate later domains and must not be mixed into this table.

## College Override Contract
Every University calendar event carries `allow_college_override`.
- `false`: future College Calendar must inherit the University event unchanged.
- `true`: future College Calendar may create a governed override referencing/adopting that University event.

The College override data model is intentionally deferred to the College Academic Setup milestone; do not prematurely add `college_id` to University calendar tables.

## Lifecycle
Phase 1 uses non-destructive `ACTIVE | INACTIVE` status. Hard deletion is not exposed in normal UI.

## Approval Engine
Academic Calendar Phase 1 does not create a second approval system. If formal Calendar approval/versioning is later required, it must integrate with the existing Common Dynamic Academic Approval Engine (ADR 009), using a Calendar-specific handler/service for validation and final lifecycle effects.


## Parent Session Date Integrity
Academic Session is the authoritative outer date boundary for the University Academic Calendar.
- Existing Calendar events must remain inside that boundary after any Session edit.
- If a proposed Session date change would strand one or more existing Calendar events outside the new range, reject the Session update.
- Do not auto-disable or auto-delete dependent events; the administrator explicitly corrects those events first.

## Audit Semantics
Lifecycle audit events must identify the actual action, not only a generic status mutation:
- `ACADEMIC_CALENDAR_ACTIVATED`
- `ACADEMIC_CALENDAR_DEACTIVATED`
- `ACADEMIC_CALENDAR_EVENT_ACTIVATED`
- `ACADEMIC_CALENDAR_EVENT_DEACTIVATED`
