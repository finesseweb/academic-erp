# curriculum_slots

Curriculum-specific ordered Slot records under `curriculum_terms`.

## Columns
- `id`
- `curriculum_term_id` — required; RESTRICT
- `course_category_id` — required reusable University Course Category; RESTRICT
- `course_type_id` — required reusable University Course Type; RESTRICT
- `credits` — curriculum/version-specific Slot Credit; required by application for new/updated Slots
- `name` — curriculum/term-specific Slot Name
- `display_order` — positive order inside the selected Term / Semester
- `selection_mode` — `MANDATORY|CHOICE`
- `min_selection` — nullable; populated only for `CHOICE`
- `max_selection` — nullable; populated only for `CHOICE`
- `status` — `ACTIVE|INACTIVE`
- `created_by`, `updated_by` — nullable user references; SET NULL
- timestamps

## Constraints
- unique `(curriculum_term_id, display_order)`
- `CHOICE` requires Minimum and Maximum Selection
- `max_selection >= min_selection`
- `MANDATORY` stores both selection-count fields as NULL
- selected Course Category and Course Type must be ACTIVE and owned by the same University

## Indexes
- `(curriculum_term_id, status, display_order)`
- `(course_category_id, status)`
- `(course_type_id, status)`
- `(selection_mode, status)`

## Reuse Rule
Course Category and Course Type are reused master records.

Curriculum Slots are not globally shared. Future Copy / Clone Structure creates new target Slot rows.

## Credit
Credit is stored directly on the Curriculum Slot as the canonical curriculum-specific value.

## Explicitly Not Stored
- mapped Course/Paper IDs
- credit totals
- structure validation results
