# ADR 217 — Academic Operations Navigation and Searchable Select Consistency

Status: IMPLEMENTED — OWNER QA REQUIRED
Date: 2026-09-24

## Decision
The College sidebar must not keep Attendance and Internal Assessment as children of Course Delivery once those workflows exist as independent operational areas.

The canonical College navigation groups are:
- Course Delivery: Course Offerings, Faculty Allocation, Rooms, Timetable, Class Scheduling.
- Attendance: Attendance, Attendance Eligibility.
- Assessment: Assessment Setup, Assignments, Quizzes, Mid Semester, Practical, Marks Entry; future Marks Approval and Internal Marks Finalization belong here.

This is a navigation/UX boundary only. It does not change the existing database hierarchy, permissions, routes, Course Offering relationships, Attendance dependencies, or Internal Assessment domain relationships.

## Select control contract
Assessment pages must follow the project-wide Searchable Select Standard. Dynamic/growing selectors such as Course Offering, Assessment Component, Faculty Allocation and Published Activity must use the shared `resources/js/components/ui/searchable-select.tsx` control instead of browser-native `<select>` controls.

The Assessment Type selector also uses the same shared searchable interaction on the Assessment Setup page for visual consistency. Small row-level state enums where search provides no value (for example ENTERED / ABSENT during Marks Entry) may remain the standard compact select.

Future Assessment, Attendance, Examination and Result pages must apply `UI_SEARCHABLE_SELECT_STANDARD.md`: dynamic ID-backed datasets use the shared SearchableSelect; do not introduce page-local "chosen"/searchable picker implementations. When a touched existing page contains a dynamic native selector, migrate it to the shared component.

## Implementation
- Split College sidebar into Course Delivery, Attendance and Assessment groups.
- Migrated Assessment Setup Course Offering and Assessment Type selectors to shared SearchableSelect.
- Migrated Assignment / Quiz / Mid Semester / Practical Assessment Component and Faculty Allocation selectors to shared SearchableSelect.
- Migrated Marks Entry Published Activity selector to shared SearchableSelect.
- Existing routes, permissions and backend validation remain unchanged.
