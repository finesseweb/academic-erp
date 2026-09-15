# Changelog Patch — Admission Application Date Picker

## 2026-08-26

- Replaced the native browser Date of Birth input on Applications / Candidate Eligibility with the project's shared `DatePicker` component.
- Preserved ISO date submission through the shared component and existing validation-error display.
- Added a UI maximum of today's date so a future candidate Date of Birth cannot be selected.
- Documented the shared DatePicker requirement for future Admission and Student date-only fields.
