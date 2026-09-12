# program_template_discipline_specializations

Optional Specialization mapping under one specific Program-Template/Discipline assignment.

## Columns
- `id`
- `program_template_discipline_id` — required; FK to `program_template_disciplines.id`; CASCADE on delete
- `specialization_id` — required; FK to `academic_disciplines.id`; RESTRICT on delete
- timestamps

## Rules
- `specialization_id` must be an active same-University `SPECIALIZATION`.
- Its `parent_id` must equal the Discipline referenced by `program_template_discipline_id`.
- A Discipline may be used without any Specialization mapping.
- One Specialization may be mapped only once under the same Program-Template/Discipline mapping.
- Removing the Discipline mapping cascades its Specialization mappings.

## Constraints / Indexes
- Unique: (`program_template_discipline_id`, `specialization_id`) as `program_tpl_disc_spec_unique`
- Index: `specialization_id` as `ptds_specialization_idx`
- FK names are explicitly shortened for MySQL identifier-length safety: `ptds_mapping_fk`, `ptds_specialization_fk`.
