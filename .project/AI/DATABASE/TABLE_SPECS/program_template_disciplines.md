# program_template_disciplines

Many-to-many mapping between Program Templates and top-level Disciplines.

## Columns
- `id`
- `program_template_id` — required; FK to `program_templates.id`; CASCADE on delete
- `discipline_id` — required; FK to `academic_disciplines.id`; RESTRICT on delete
- timestamps

## Rules
- `discipline_id` must represent an active same-University `DISCIPLINE` at application validation time.
- One Discipline may be mapped only once to the same Program Template.
- Removing a Program Template cascades its Discipline mappings.
- A mapped Discipline cannot be physically deleted while referenced.

## Constraints / Indexes
- Unique: (`program_template_id`, `discipline_id`) as `program_tpl_discipline_unique`
- Index: `discipline_id` as `ptd_discipline_idx`
- FK names are explicitly shortened for MySQL identifier-length safety: `ptd_template_fk`, `ptd_discipline_fk`.
