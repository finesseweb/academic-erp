# ADR 065 — Admission Form AcademicSelect Runtime Restoration

## Context
After the advanced Admission Form field-rule patches, opening **Add Field** on the University Admission Form Setup could blank the Inertia page. Browser runtime evidence showed `ReferenceError: AcademicSelect is not defined` from the Admission Form builder. SSR itself completed successfully; the failure occurred only when the client rendered the field dialog.

## Decision
The Admission Form builder must keep Academic Applicability inside the existing field dialog and must render it through a defined, reusable `AcademicSelect` helper/component. The component is restored defensively rather than removing Academic Applicability or creating a second builder path.

The restored runtime must preserve all existing field-builder capabilities:
- generic conditional visibility and academic applicability;
- intrinsic text/number validation;
- cross-field NUMBER/DATE comparison;
- generic copy-from-field behavior;
- dynamic DATE age validation;
- panel and field display order;
- DRAFT-only structural editing and University Base -> College Extension governance.

## Runtime boundary
This is a frontend runtime correction. It does not change schema, RBAC, route contracts, template lifecycle, or Admission business hierarchy. Font preload warnings are unrelated to this failure.

## Status
IMPLEMENTED / OWNER QA REQUIRED — 2026-09-01.
