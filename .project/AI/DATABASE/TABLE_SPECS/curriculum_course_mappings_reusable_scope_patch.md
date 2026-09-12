# curriculum_course_mappings — Reusable Scope Patch

## Correct uniqueness scope
The legacy unique constraint on:

`curriculum_slot_id + course_id`

is too restrictive because Course Master is reusable across Disciplines and Specializations.

The logical uniqueness boundary is:

`curriculum_slot_id + discipline_id + specialization_id + course_id`

Application/service validation explicitly treats `specialization_id = NULL` as the `No Specialization` context and blocks an exact duplicate there as well.

## Preserved architecture
- Course Master does not own Discipline or Specialization.
- Curriculum Course Mapping owns the Discipline/Specialization context.
- The same Course may therefore appear more than once in one Slot when each row belongs to a different Discipline/Specialization context.
- Course Category, Course Type, University ownership, Program Template Discipline membership and DRAFT-only edit rules remain unchanged.
