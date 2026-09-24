# ADR 213 — Shared Scheduling Time Picker Standard

**Status:** IMPLEMENTED — OWNER QA REQUIRED  
**Date:** 2026-09-23

## Context
Interview Scheduling already established the ERP-consistent time-only interaction through the shared `TimePicker`: explicit hour, minute and AM/PM controls. Course Delivery Timetable still used browser-native `input type="time"`, causing browser-dependent UI and inconsistent scheduling behavior.

## Decision
The shared `TimePicker` is the canonical control for time-only scheduling fields. Course Delivery Timetable create/edit must use it for Start Time and End Time with a 5-minute step, matching Interview Scheduling. The UI continues to submit `HH:mm`, so existing Laravel validation, persistence, overlap checks, teaching-hour validation and Class Schedule snapshot behavior remain unchanged.

Future new or modified ERP scheduling forms must reuse the shared `TimePicker` when their business field is time-only. Page-specific/native time controls require an explicitly documented accessibility or technical exception. Date-time fields continue to use the shared `DateTimePicker` where a combined date/time value is required.

## Consequences
- Consistent scheduling UX across Interview Scheduling, Timetable and future modules.
- No schema migration.
- No change to time storage or backend contracts.
- OWNER QA should verify create/edit defaults, AM/PM conversion, 12 AM/PM boundaries, minute-step selection and validation errors.
