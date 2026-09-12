# academic_sessions

University-owned academic periods. Columns: `id`, `university_id` (restrict FK), `name`, per-University unique `code`, `starts_on`, `ends_on`, `status` (`PLANNED|ACTIVE|CLOSED|ARCHIVED`), `is_current`, timestamps. Indexed by University/status and University/current. Application transaction and row locking enforce one current session per University.
