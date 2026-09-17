# Table: `student_profile_values`

## Purpose
Preserves selected dynamic Admission-form values that are intentionally promoted into the Student profile without turning the stable `students` table into a dynamic schema.

## Columns
- `student_id` — Student owner.
- `source_application_field_id` — nullable source dynamic field reference.
- `profile_key` — stable Student-profile attribute key.
- `label_snapshot` — label at promotion/import time.
- `value_text`, `value_json` — scalar/structured value.
- file snapshot metadata: `file_path`, `file_name`, `file_mime`, `file_size`.
- timestamps.

## Rules
- Unique (`student_id`, `profile_key`).
- Only fields explicitly marked `STUDENT_PROFILE` may be promoted by ENR-2.
- `APPLICATION_ONLY` values remain only in Admission/Application history.
- Source field uses nullable reference so a profile snapshot remains readable if an unused source definition is later removed under allowed cleanup rules.

## ENR-2 population rule
On first Admission-sourced Student creation, only submitted dynamic values whose source field is `STUDENT_PROFILE` and has a non-empty `student_profile_key` are copied. APPLICATION_ONLY fields remain solely in the historical Application record.
