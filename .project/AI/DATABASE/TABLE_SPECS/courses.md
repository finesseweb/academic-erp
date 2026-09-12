# courses

## Status
IMPLEMENTED — 2026-08-21

## Purpose
University-owned reusable Course / Subject Master.

## Columns
- `id` — primary key.
- `university_id` — owning University.
- `course_category_id` — required Course Category.
- `course_type_id` — required Course Type.
- `name` — Course / Subject name.
- `code` — University-unique Course Code.
- `description` — optional description.
- `display_order` — presentation order.
- `status` — `ACTIVE` / `INACTIVE`.
- timestamps.

## Relationships
- `courses.university_id` → `universities.id`
- `courses.course_category_id` → `course_categories.id`
- `courses.course_type_id` → `course_types.id`

## Validation / scope rules
- Course Code is unique within the University.
- Selected Course Category must belong to the University and be ACTIVE.
- Selected Course Type must belong to the University and be ACTIVE.
- Course Master does not directly store Program Template, Discipline or Specialization.
- Course Master does not currently store term/semester, credits or L-T-P/contact hours.

## Future relationship
Curriculum / Course Mapping will bind the reusable Course / Subject to the relevant Program Template, Discipline, optional Specialization and term/semester, together with curriculum-specific academic values.
