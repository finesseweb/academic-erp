# degree_levels

University-owned ordered degree classifications. Columns: `id`, `university_id` (restrict FK), `name`, per-University unique `code`, nullable `description`, `display_order`, `status` (`ACTIVE|INACTIVE`), timestamps. Indexed by University/status/display order. Lifecycle is non-destructive.
