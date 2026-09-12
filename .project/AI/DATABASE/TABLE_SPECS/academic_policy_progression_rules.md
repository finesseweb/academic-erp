# academic_policy_progression_rules — LEGACY / REMOVED

The original Phase 5 one-row-per-policy table was too restrictive because it could not represent different progression rules for different Terms or combined multi-Term checkpoints.

It is superseded by:
- `academic_policy_progression_rule_sets`
- `academic_policy_progression_rule_terms`

Migration `2026_08_24_180000_refactor_academic_policy_progression_to_rule_sets.php` converts an existing legacy row into one **Default Progression Rule** before dropping the legacy table.
