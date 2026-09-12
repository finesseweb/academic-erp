# academic_policy_assessment_exam_rules

One-to-one general Assessment / Examination rule set for an Academic Policy version.

| Column | Purpose |
|---|---|
| id | Primary key |
| academic_policy_id | Parent Academic Policy; unique |
| minimum_overall_pass_percent | Generic overall pass threshold, nullable |
| require_separate_component_pass | Future result engine must separately evaluate configured assessment components |
| absence_result | FAIL / INCOMPLETE / AS_PER_EXAM_RULE |
| allow_grace_marks | Whether policy permits grace |
| maximum_grace_marks | Maximum permitted grace marks when enabled |
| allow_improvement_exam | Whether policy permits improvement workflow |
| allow_supplementary_exam | Whether policy permits supplementary workflow |
| notes | Regulation/reference notes |
| created_by / updated_by | User audit references |
| timestamps | Laravel timestamps |

Foreign key name is deliberately short: `apaer_policy_fk`.
Unique index: `apaer_policy_uq`.

Component-specific definitions are intentionally absent. They will reference the future Assessment Scheme instead of hard-coding exam component types.
