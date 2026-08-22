# Curriculum Course / Paper Mapping

## Source-of-Truth Design
Course Master stays reusable and intentionally does not bind Program Template, Discipline or Specialization.

Curriculum Course Mapping is the binding point for:
Program Template -> Discipline -> optional Specialization -> Course / Subject -> Term / Semester -> Slot.

## Mapping fields
- Discipline — required
- Specialization — optional
- Course / Subject — required
- Display Order
- Status

## Discipline source
Only active Disciplines already selected in the Curriculum's Program Template are available, through `program_template_disciplines`.

## Specialization source
Optional. After selecting Discipline, only active Specializations mapped under that exact Program Template Discipline are available, through `program_template_discipline_specializations`.

The Specialization must also be a child of the selected Discipline in `academic_disciplines`.

## Course source
Course / Subject dropdown remains filtered by:
- same University
- ACTIVE
- Course Category = Slot Course Category
- Course Type = Slot Course Type

Course itself does not own Discipline/Specialization. The mapping owns that academic context.

## Example
BA Program Template:
- English
  - English Literature
  - Linguistics
- Hindi

One Choice MINOR/Theory Slot can map:
- English / English Literature / Introduction to Literature
- Hindi / no specialization / Hindi Poetry

## Validation
Backend verifies:
- Curriculum -> Term -> Slot context
- DRAFT-only edits
- Discipline belongs to selected Program Template
- optional Specialization belongs to that Program Template Discipline and parent Discipline
- same University and ACTIVE state
- Course Category and Course Type match Slot
- duplicate Course in same Slot is blocked

## Historical upgrade
`discipline_id` and `specialization_id` are nullable at DB level so old mapping rows migrate safely.
New mappings require Discipline.

## Deliberate non-change
Do not add Discipline or Specialization columns to `courses`.

## Still deferred
- Credit
- L-T-P/contact hours
- credit totals
- final Structure Validation
- Copy / Clone execution UI


## Edit Mapping
While the parent Curriculum is `DRAFT`, an existing Course / Paper Mapping may be edited.

Editable mapping context:
- Discipline
- optional Specialization
- Course / Subject

This does not edit Course Master. It edits only the contextual Curriculum mapping.

Mappings created before Discipline/Specialization support may show `—`; use Edit to assign the missing context.

When Curriculum is `ACTIVE` or `RETIRED`, Edit is unavailable and backend DRAFT-only protection still applies.

Audit event:
- `CURRICULUM_COURSE_MAPPING_UPDATED`
