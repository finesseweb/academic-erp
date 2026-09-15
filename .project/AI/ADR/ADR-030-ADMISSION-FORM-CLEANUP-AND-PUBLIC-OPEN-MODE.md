# ADR 030 — Admission Form Cleanup FK Order and Public Link Open Mode

## Status
Accepted — Stage 1 QA correction.

## Decision
1. Test Data Cleanup must delete `college_admission_form_field_conditions` before deleting a Form Template tree because `source_field_id` intentionally uses a RESTRICT foreign key. The same dependency order applies to individual template cleanup and Full Academic Test Reset.
2. A College-owned form mapping stores `public_open_mode` as `SAME_WINDOW` or `NEW_WINDOW`. The setting controls how the College UI opens the generated public application URL; it does not change the public URL, Admission Cycle gating, or candidate workflow.
3. Default is `SAME_WINDOW`.

## Rationale
The cleanup service must respect database referential integrity instead of disabling foreign keys. Public-link navigation is a presentation preference and therefore belongs to the College mapping/publish configuration, not the University Base Template.
