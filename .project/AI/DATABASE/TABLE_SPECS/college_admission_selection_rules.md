# college_admission_selection_rules

Versioned College-owned Merit / Roster / Selection configuration for one exact effective Intake admission seat bucket.

## Parent Context
Required parent: `college_program_intakes.id` + `bucket_key`.
Optional reservation context: `college_program_reservation_plans.id`.

## Core Columns
- `id`
- `college_program_intake_id`
- `college_program_reservation_plan_id` nullable
- `bucket_type`
- `bucket_key`
- `basis_capacity`
- `version_no`
- `name`
- `code`
- `selection_mode` — `MERIT`, `ENTRANCE`, `INTERVIEW`, `COMBINED`
- `merit_weight_percent`
- `entrance_weight_percent`
- `interview_weight_percent`
- `minimum_merit_score` nullable, normalized 0-100
- `minimum_entrance_score` nullable, normalized 0-100
- `minimum_interview_score` nullable, normalized 0-100
- `minimum_final_score` nullable, normalized 0-100 and used only for COMBINED mode
- `minimum_qualifying_score` legacy compatibility column; new code must not use it
- `roster_rule_reference` nullable
- `tie_breaker_rules` nullable human-readable policy wording only; this field is not executable ranking logic
- `notes` nullable
- `status` — `INACTIVE`, `ACTIVE`, `RETIRED`
- audit user/timestamps

## Structured Tie-breakers
Executable tie-break order is stored in child table `college_admission_selection_rule_tiebreakers`.

The parent free-text `tie_breaker_rules` field may preserve official policy wording or special explanation, but future merit generation must never parse that text to decide rank.

## Threshold Rule
All stored admission selection thresholds use a normalized 0-100 scale.

- MERIT: Merit weight = 100; Entrance/Interview = 0; `minimum_merit_score` may be used.
- ENTRANCE: Entrance weight = 100; Merit/Interview = 0; `minimum_entrance_score` may be used.
- INTERVIEW: Interview weight = 100; Merit/Entrance = 0; `minimum_interview_score` may be used.
- COMBINED: at least two of Merit / Entrance / Interview must have positive weights and all three weights together must total exactly 100%. A minimum may be set only for a component with positive weight; `minimum_final_score` is optional for the final weighted score.

## Reservation Rule
- No Reservation Plan: full seat bucket is Open/General and Selection is allowed.
- Reservation defined + INACTIVE: that bucket is blocked until Reservation is ACTIVE.
- Reservation defined + ACTIVE: Selection consumes and records that Reservation Plan.

## Keys / Indexes
- Unique: (`college_program_intake_id`, `bucket_key`, `version_no`)
- Unique: (`college_program_intake_id`, `bucket_key`, `code`, `version_no`)
- Index: (`college_program_intake_id`, `bucket_key`, `status`)
- Index: (`college_program_reservation_plan_id`, `status`)

## Lifecycle
Create → INACTIVE; only INACTIVE is editable; activate against the current effective bucket; activating a newer version retires the previous ACTIVE version for the same bucket.

Activation additionally requires at least one structured tie-breaker so future merit ranking has a deterministic order when primary scores are equal.

## Future Consumption Contract
Interview weight/threshold configuration belongs to the Selection Rule only. Candidate interview schedules, panels, evaluations and awarded scores must be stored later in the Admission Processing / Interview Evaluation layer. Merit/Roster generation consumes normalized Interview scores together with Merit/Entrance scores according to the ACTIVE rule version. Student Lifecycle must not own or recalculate admission Interview logic.
