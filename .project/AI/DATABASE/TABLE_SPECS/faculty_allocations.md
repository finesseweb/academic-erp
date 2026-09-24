# `faculty_allocations`

Phase 13 bridge from `course_offerings` to an existing College Faculty user. Nullable `section_id` narrows delivery to a same-Batch Section; null means the whole Batch.

Fields: `course_offering_id`, nullable `section_id`, `faculty_user_id`, `teaching_role`, nullable `weekly_load`, `status`, nullable `notes`, actor fields and timestamps. Restrictive foreign keys and server validation protect ownership, eligibility, lifecycle and duplicate integrity.
