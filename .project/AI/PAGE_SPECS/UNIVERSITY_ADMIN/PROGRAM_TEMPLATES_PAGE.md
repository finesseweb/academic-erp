# Program Templates

## Status
Implementation: IMPLEMENTED. Review: PENDING_REVIEW. Updated 2026-08-21.

Reusable University program blueprints for later College offerings and curricula.

Each Program Template requires one active same-University Degree and one or more active top-level Disciplines. Every selected Discipline can optionally bind multiple active Specializations belonging to that Discipline. Specializations are never selected independently of their parent Discipline.

## Create / Edit UX
The create/edit dialog uses a wide responsive layout and does not add a separate CSS file. It uses the existing semantic Tailwind/theme tokens and shared UI components, so it follows all configured application themes.

Academic Structure selection uses a scalable two-pane selector:

- Left pane: searchable, scrollable Discipline list.
- `All` and `Selected` filtering for large Discipline sets.
- Discipline selection is multi-select.
- Right pane: only the currently focused selected Discipline's Specializations.
- Specializations are searchable and independently multi-select per Discipline.
- Switching between Disciplines preserves all selections.
- A Discipline with no selected Specialization remains a valid mapping.
- Both panes have controlled height/scroll so 30, 50 or more Disciplines/Specializations do not make the dialog excessively tall.
- On smaller screens the panes stack responsively.

## List / View UX
Program Template rows remain compact. `Academic Structure` is collapsed by default.

The collapsed summary shows total Discipline and Specialization counts. Users may expand `Academic Structure` to inspect Discipline -> Specialization details without entering Edit mode. The expanded area uses controlled internal scrolling for large mappings, keeping the page compact while still information-rich.

## Validation / Data Rules
- Degree must be active and owned by the same University.
- At least one Discipline is required for create/update.
- Every mapped Discipline must be active, same-University and `kind = DISCIPLINE`.
- Every mapped Specialization must be active, same-University and `kind = SPECIALIZATION`.
- Every Specialization must have `parent_id` equal to its mapped Discipline.
- Duplicate Discipline or Specialization mappings are not allowed.

## Persistence
Core Program Template fields remain in `program_templates`.

Academic Structure persists through:
- `program_template_disciplines`
- `program_template_discipline_specializations`

Migration `2026_08_21_150000_make_program_template_disciplines_many_to_many` preserves existing single-Discipline mappings before removing the old direct Discipline/Specialization columns. Explicit short FK names are used to stay within MySQL identifier limits.

## Security / Lifecycle
Permissions: `program_template.view`, `program_template.create`, `program_template.update`, `program_template.disable`.

Audit events continue under `PROGRAM_TEMPLATE_*`. Status remains non-destructive `ACTIVE|INACTIVE`.
