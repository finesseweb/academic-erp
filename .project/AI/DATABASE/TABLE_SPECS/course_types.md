# course_types

University-owned Course Type master for later Course / Subject Master and curriculum usage.

## Columns
- `id` — primary key.
- `university_id` — required FK to `universities.id`, RESTRICT on delete.
- `name` — required string(120).
- `code` — required string(40), unique per University.
- `description` — nullable text.
- `display_order` — unsigned small integer, default 0.
- `status` — string(20), default `ACTIVE`; application values `ACTIVE` / `INACTIVE`.
- timestamps.

## Constraints / Indexes
- Unique: (`university_id`, `code`).
- Non-unique: `course_type_scope_order_idx (university_id, status, display_order)`.

## Ownership
University-owned academic master. College delegation is not enabled at this stage.

## Future Use
Course / Subject Master may reference `course_types.id`. Credits, term placement, L-T-P/contact hours and curriculum selection rules do not belong in this table.
