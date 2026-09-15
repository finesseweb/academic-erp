# Changelog Patch — Admission Form Stage 1

Date: 2026-08-27

- Added dynamic Admission Form Template/Step/Field/Option masters.
- Added scoped Form Mapping resolution with most-specific-wins inheritance.
- Added scoped Application Fee Rules and application fee snapshot fields.
- Added dynamic Application Field Values including file metadata/storage path.
- Added Admission Form Setup page and permissions.
- Extended internal Admission Application Entry to resolve/render mapped dynamic forms in the existing ERP theme.
- Added REGULAR vs DIRECT admission mode. DIRECT does not require Selection Rule; REGULAR behavior remains unchanged.
- Added documentation ADR 024, Page Spec, database specs, state patch, relationship/schema updates.

## 2026-08-27 — Form Builder Validation UX
- Added visible validation summary and inline field errors to University and College field-create dialogs.
- Added friendly Field Key guidance and choice-option requirements.
- Added explicit backend validation messages for common field-creation errors.
- No schema change.
