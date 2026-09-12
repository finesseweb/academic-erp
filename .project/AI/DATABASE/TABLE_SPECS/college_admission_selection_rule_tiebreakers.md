# college_admission_selection_rule_tiebreakers

Ordered, machine-readable tie-break criteria for one College Admission Selection Rule version.

## Parent
`college_admission_selection_rules.id`

## Columns
- `id`
- `college_admission_selection_rule_id`
- `priority` — 1 is evaluated first
- `criterion`
  - `QUALIFYING_EXAM_SCORE`
  - `ENTRANCE_SCORE`
  - `INTERVIEW_SCORE`
  - `RELEVANT_SUBJECT_SCORE`
  - `DATE_OF_BIRTH`
  - `APPLICATION_SUBMITTED_AT`
- `comparison_direction` — `ASC` or `DESC`
- `criterion_reference` nullable; required by application logic for `RELEVANT_SUBJECT_SCORE` (example: English / Mathematics / Physics)
- timestamps

## Interpretation
- Score criteria, including Interview Score: `DESC` = higher first; `ASC` = lower first.
- Date of Birth: `ASC` = older first; `DESC` = younger first.
- Application Submitted At: `ASC` = earlier first; `DESC` = later first.

## Constraints
- FK to parent Selection Rule with cascade delete.
- Unique (`college_admission_selection_rule_id`, `priority`).
- Maximum 10 criteria per rule in application validation.
- Duplicate criterion + reference combinations are rejected by service validation.
- At least one structured tie-breaker is required before a Selection Rule can be activated.

## Ranking Contract
Future Merit / Roster generation evaluates Priority 1, then Priority 2 only for candidates still tied, then continues in order until the tie is resolved. It must consume these rows directly and must not infer executable logic from free-text notes.
