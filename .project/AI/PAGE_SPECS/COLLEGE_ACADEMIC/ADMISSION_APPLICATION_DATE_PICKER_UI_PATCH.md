# Admission Application — Date Picker UI Consistency Patch

## Scope

Applications / Candidate Eligibility → Add/Edit Admission Application.

## Mandatory UI rule

- `Date of Birth` must use the shared `resources/js/components/ui/date-picker.tsx` `DatePicker` component.
- Do not use native browser `input type="date"` controls for this field.
- Preserve the project-standard date display, calendar dialog, month/year selection, validation styling, and hidden ISO `YYYY-MM-DD` form value supplied by the shared component.
- Candidate Date of Birth must not allow a future date in the UI; backend validation remains authoritative.

## Reuse rule for future Admission / Student pages

Any date-only field added to Admission, Candidate, Student Lifecycle, or related College Academic screens must reuse the shared `DatePicker` unless a documented exception is approved. Do not introduce page-local/native date controls when the shared component satisfies the requirement.
