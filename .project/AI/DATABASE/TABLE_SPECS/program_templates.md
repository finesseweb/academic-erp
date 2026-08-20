# program_templates
University-owned blueprint with RESTRICT FKs to University, required Degree and optional academic discipline. Contains unique University code, name, `SEMESTER|YEAR|TRIMESTER` structure, duration terms, description, display order, status and timestamps. Non-unique `program_tpl_scope_order_idx (university_id, degree_id, status, display_order)` supports governed lists.
