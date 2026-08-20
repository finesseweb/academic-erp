# academic_disciplines
University-owned self-referencing discipline tree. Required University FK and nullable RESTRICT parent FK; kind is `DISCIPLINE|SPECIALIZATION`. Name, per-University unique code, description, order, status and timestamps. Non-unique `acad_disc_scope_order_idx (university_id, kind, status, display_order)` supports lists and parent selectors.
