# program_templates

University-owned reusable program blueprint. A Program Template belongs to one active same-University Degree, but Discipline and Specialization assignments are no longer stored as direct foreign keys on this table.

## Core Columns
- `id`
- `university_id` — required University owner; RESTRICT on delete
- `degree_id` — required Degree; RESTRICT on delete
- `name` — template name
- `code` — unique within the University
- `term_structure` — `SEMESTER|YEAR|TRIMESTER`
- `duration_terms`
- `description` — optional
- `display_order`
- `status` — `ACTIVE|INACTIVE`
- timestamps

## Academic Structure Binding
A template may contain multiple top-level Disciplines through `program_template_disciplines`.

Each selected Discipline may contain zero or more allowed Specializations through `program_template_discipline_specializations`. A Specialization must be an active `academic_disciplines` row with `kind = SPECIALIZATION` whose `parent_id` matches the mapped Discipline.

This supports structures such as:

- BA -> English -> English Literature
- BA -> Hindi -> Hindi Literature, Hindi Language
- BA -> History -> Ancient History, Modern History
- BA -> Political Science -> no specialization

Discipline selection is required at application level for new/updated templates. Specialization selection is optional per Discipline.

The previous direct `program_templates.discipline_id` / development `specialization_id` representation is migrated into mapping tables by `2026_08_21_150000_make_program_template_disciplines_many_to_many` and then removed.

## Indexing
`program_tpl_scope_order_idx (university_id, degree_id, status, display_order)` supports governed ordered lists. See the mapping-table specifications for mapping uniqueness and lookup indexes.
