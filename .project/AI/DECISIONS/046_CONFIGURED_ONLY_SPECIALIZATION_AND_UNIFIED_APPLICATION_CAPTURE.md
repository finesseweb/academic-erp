# ADR 046 — Configured-Only Specialization and Unified Application Capture

Date: 2026-08-31
Status: Accepted

## Decision

Admission application entry, whether PUBLIC applicant self-service or INTERNAL College Admin entry, must use the same configured academic and form sources:

1. Admission Cycle resolves the exact Program Offering.
2. Program Offering resolves the approved Curriculum and Program Template.
3. Discipline choices come only from Program Template -> Discipline mappings.
4. Specialization is shown only when the selected Discipline actually has one or more configured Specializations.
   - no configured Specialization: field is not rendered and `specialization_id` remains NULL;
   - configured + optional: General / No Specialization is allowed;
   - configured + required: one configured Specialization is mandatory.
5. Mandatory Curriculum mappings are inherited automatically and are not manually selected in the application.
6. Only Curriculum slots with `CHOICE` selection mode are shown for applicant/admin choice.
7. Choice display is grouped by Course Category and mapping-level `Offered From Discipline`.
8. Dynamic Admission Form Template steps, panels, fields, conditions and fee resolution are shared by Public and Internal application entry.

## Seat / Reservation Boundary

Application capture is not seat-capacity gated. No Admission Seat Bucket is required while creating/submitting a new application.

Reservation plans, quota/roster processing, Selection Rule evaluation and Seat Allocation are downstream Admission stages. Existing legacy applications that already contain seat-bucket choices remain readable/processable for compatibility, but new application entry does not create such choices.

## Curriculum Mapping Specialization

`CurriculumCourseMapping.specialization_id` means "this paper applies only to this specialization". It does not make specialization globally mandatory. NULL means the mapping applies to the entire selected Discipline.

The Curriculum Mapping UI hides the specialization selector when the selected Discipline has no configured Specializations and displays NULL mappings as `Entire Discipline`.
