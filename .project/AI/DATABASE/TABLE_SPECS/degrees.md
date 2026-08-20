# degrees

University-owned degree/award. FK `university_id` and required `degree_level_id` use RESTRICT. Columns include name, per-University unique code, description, optional typical duration years, display order, status and timestamps. Composite index `(university_id, degree_level_id, status, display_order)` supports governed ordered lists.
