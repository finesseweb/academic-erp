# Admission Cycle — Shared DatePicker Consistency Patch

## Purpose
Admission Cycle date inputs must follow the existing ERP-wide date control standard rather than browser-native `input[type=date]` controls.

## Required UI behavior
- `Application Start`, `Application End`, `Admission Start`, and `Admission End` use `resources/js/components/ui/date-picker.tsx` (`DatePicker`).
- Do not introduce page-specific or browser-native date controls when the shared `DatePicker` can satisfy the requirement.
- The selected Program Offering supplies the Academic Session date bounds.
- All four Admission Cycle dates must remain inside that inherited Academic Session.
- `Application End` cannot precede `Application Start`.
- `Admission Start` cannot precede `Application Start`.
- `Admission End` cannot precede `Admission Start`.
- The frontend restrictions complement, but never replace, Laravel/service validation.
- Existing responsive modal, semantic-token, validation, accessibility and theme rules remain mandatory.

## Consistency rule for future modules
Before adding any date field, implementation must first inspect and reuse the shared `DatePicker` pattern already used by Academic Sessions, Academic Calendar, Signatories, Policies and role/scope assignment flows. A native date input requires an explicit documented exception.

## Patch status
Implemented for College Admission Cycle after QA identified four inconsistent native date inputs.
