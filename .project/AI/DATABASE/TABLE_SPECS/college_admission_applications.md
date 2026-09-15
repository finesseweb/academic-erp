# college_admission_applications

College-scoped Admission Application header containing candidate identity/contact data and application lifecycle. Program/seat choices are normalized into `college_admission_application_choices`.

## Core Columns
- `id`
- `college_id`
- `college_admission_cycle_id`
- `application_no` — system-generated human-readable College/Cycle reference
- `external_reference` — nullable external portal/reference
- `candidate_name`
- `email` — nullable
- `phone` — nullable
- `date_of_birth`
- `status` — `DRAFT`, `SUBMITTED`, `WITHDRAWN`
- `submitted_at`
- `withdrawn_at`
- `remarks`
- `created_by`
- `updated_by`
- timestamps

## Keys / Indexes
- Unique: (`college_id`, `application_no`)
- Index: (`college_id`, `college_admission_cycle_id`, `status`)
- Index: (`college_id`, `candidate_name`)

## Rules
- Application Cycle must belong to the same College.
- New application starts DRAFT.
- Only DRAFT is editable/submittable.
- Submit is allowed only while the ACTIVE Admission Cycle is inside its Application Start/End dates.
- `submitted_at` is historical/auditable and later supplies the Application Submitted At tie-break value.
- Submitted applications are not silently relinked to new Selection Rule versions.
- Student Lifecycle must reference the resulting confirmed Admission later; it must not convert this table into the Student master prematurely.
