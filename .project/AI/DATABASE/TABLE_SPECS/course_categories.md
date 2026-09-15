# course_categories
University-owned course classification. Required University RESTRICT FK; name, per-University unique code, controlled category group, description, display order, status and timestamps. Non-unique `course_cat_scope_order_idx (university_id, category_group, status, display_order)` supports curriculum selectors and ordered administration.
