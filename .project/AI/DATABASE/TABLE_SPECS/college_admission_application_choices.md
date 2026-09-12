# college_admission_application_choices

Ordered Program Choice / admission seat context rows for a College Admission Application.

## Core Columns
- `id`
- `college_admission_application_id`
- `preference_no`
- `college_program_intake_id`
- `college_program_reservation_plan_id` — nullable; Reservation is optional per bucket
- `college_admission_selection_rule_id` — exact rule version locked on submit
- `bucket_type` — `PROGRAM`, `DISCIPLINE_GENERAL`, `SPECIALIZATION`
- `bucket_key`
- `basis_capacity`
- `eligibility_status` — `PENDING`, `ELIGIBLE`, `INELIGIBLE`
- `eligibility_reason`
- `eligibility_checked_at`
- `eligibility_checked_by`
- timestamps

## Keys / Indexes
- Unique: (`college_admission_application_id`, `preference_no`)
- Unique: (`college_admission_application_id`, `college_program_intake_id`, `bucket_key`)
- Index: (`college_admission_selection_rule_id`, `eligibility_status`)
- Index: (`college_program_reservation_plan_id`, `eligibility_status`)

## Rules
- Choice Offering/Intake Academic Session must equal the Application Admission Cycle Academic Session.
- Offering and Intake must be ACTIVE for new/updated/submitted applications.
- If Reservation exists for the bucket it must be ACTIVE; when Reservation is absent the bucket remains Open/General.
- An ACTIVE Selection Rule is mandatory for the exact Intake + bucket.
- The active Selection Rule reservation context must match the current bucket Reservation context.
- On submit, Selection Rule / Reservation / bucket capacity are revalidated and persisted as the historical processing context.
- Eligibility is per choice and is preliminary/basic only. Score thresholds and ranking are not stored here.
