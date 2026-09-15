# college_admission_cycles

College-owned Admission Cycle header anchored to one exact College Program Offering. This is the first operational record in the Admission phase after Program Offering, Intake / Seat Capacity, optional Reservation / Seat Distribution, and Selection Rules are configured.

## Scope
College-scoped and Program-Offering-scoped. `college_program_offering_id` is the authoritative academic parent. College, Academic Session, Degree/Program and Curriculum context are derived through that Program Offering.

## Core Columns
- `id`
- `college_id`
- `college_program_offering_id` — authoritative Admission Cycle parent
- `academic_session_id` — derived compatibility snapshot from Program Offering; must not be independently selected by UI/business logic
- `name`
- `code`
- `application_start_date`
- `application_end_date`
- `admission_start_date`
- `admission_end_date`
- `status` — `INACTIVE`, `ACTIVE`, `CLOSED`
- `remarks`
- `created_by`
- `updated_by`
- timestamps

## Keys / Indexes
- FK `college_program_offering_id` -> `college_program_offerings.id`
- Unique: (`college_id`, `code`)
- Index: (`college_program_offering_id`, `status`)
- Existing session index may remain for compatibility/reporting.

## Lifecycle Rules
- New cycle starts `INACTIVE`.
- Create/Edit must select an ACTIVE Program Offering belonging to the same College.
- Academic Session is inherited from the selected Program Offering.
- Admission Cycle dates must remain inside the Academic Session of that Program Offering.
- Activation requires that exact Program Offering to remain ACTIVE, its Intake to be ACTIVE, and at least one eligible seat bucket under that offering to have an ACTIVE Selection Rule.
- Reservation is optional; where a Reservation Plan exists for a selected bucket, it must be ACTIVE.
- `CLOSED` is terminal in the current implementation; a closed cycle cannot be edited or reopened.
- An ACTIVE cycle cannot be moved to another Program Offering without first being deactivated.

## Application Boundary
An Application belongs to one Admission Cycle, therefore one Program Offering. Candidate choices under that Application are ordered Admission Seat Bucket / specialization choices inside the same Program Offering. Applications must not use a seat bucket from another Program Offering.

## Historical / Legacy Compatibility
The Program Offering anchor was introduced after an earlier session-only draft. Migration `2026_08_26_090695_anchor_admission_cycles_to_program_offerings` adds the authoritative Offering FK. Legacy rows are auto-mapped only when exactly one ACTIVE offering exists for their College + Session; otherwise they remain non-activatable until an administrator edits them and selects the correct Program Offering.
