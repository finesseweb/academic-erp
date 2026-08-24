# academic_policy_progression_rule_terms

Mapping table connecting a Progression Rule Set to one or more source `curriculum_terms`.

Columns:
- `progression_rule_set_id`
- `curriculum_term_id`
- `display_order`
- timestamps

Unique:
`(progression_rule_set_id, curriculum_term_id)`

Purpose:
Allows Semester 1 alone, Semester 1 + 2, Semester 1 + 2 + 3, or any regulation-approved combination to be evaluated without hard-coded columns.
