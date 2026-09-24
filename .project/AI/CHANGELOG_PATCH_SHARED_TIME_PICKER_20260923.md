# Changelog Patch — Shared Scheduling Time Picker — 2026-09-23

- Replaced Course Delivery Timetable browser-native Start/End time inputs with the existing shared `TimePicker` used by Interview Scheduling.
- Timetable create/edit now uses hour, minute and AM/PM selectors with a 5-minute step while retaining the existing `HH:mm` backend payload.
- Added the Shared Time Picker Standard to UI/UX guidance so future time-only scheduling forms reuse the same component instead of introducing native/page-specific controls.
- Updated Course Delivery page specification and implementation state.
- No database migration or backend contract change.
