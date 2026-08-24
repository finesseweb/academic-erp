# academic_policy_progression_rule_sets

Stores multiple version-bound progression rule sets under one Academic Policy.

Core columns:
- `academic_policy_id`
- `curriculum_id` nullable for Default rule
- `name`
- `applies_to_all_stages`
- `evaluation_mode` = `COMBINED|EACH_TERM`
- `target_curriculum_term_id`
- credit / SGPA / CGPA / backlog thresholds
- mandatory-course flag
- carry-forward / detention / year-back / re-admission permissions
- maximum attempts per course
- `display_order`
- notes / actor fields / timestamps

Relationships:
- belongs to `academic_policies`
- optional selected `curricula`
- optional target `curriculum_terms`
- many source Terms through `academic_policy_progression_rule_terms`

No fixed semester identifiers are stored.
