# Curriculum Course / Paper Mapping — Reusable Discipline Scope Patch

## Rule
Course / Subject Master records remain reusable across academic contexts.

A Course already mapped in one Curriculum Slot must remain available for mapping to another eligible Discipline or Specialization in that same Slot.

The exact duplicate boundary is:

`Curriculum Slot + Discipline + Specialization (nullable) + Course / Subject`

Only that exact combination is blocked.

## Example
For Slot `MIC1`:

Allowed:
- English / no specialization / Introduction to Psychology
- Sociology / no specialization / Introduction to Psychology
- English / English Literature / Introduction to Psychology

Blocked:
- adding English / no specialization / Introduction to Psychology a second time in `MIC1`

## UI behavior
- `Map Course / Paper` is disabled only when no compatible ACTIVE Course Master exists for the Slot's Course Category + Course Type, or the Curriculum is not editable.
- Selecting a Discipline / Specialization filters out only Courses already mapped to that exact context.
- Changing Discipline or Specialization clears the current Course selection to prevent stale cross-context submission.
- When all compatible Courses are already used for the selected exact context, show a contextual message instead of globally disabling reusable courses.

## Backend behavior
Server validation must enforce the same exact-context duplicate rule. Never validate uniqueness by Slot + Course alone.
