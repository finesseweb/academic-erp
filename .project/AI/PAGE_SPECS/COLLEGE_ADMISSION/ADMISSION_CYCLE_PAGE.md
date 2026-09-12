# Admission Cycle Page

## Route
`/college/{college}/admission-cycles`

## Purpose
Open a controlled application/admission window for one exact ACTIVE College Program Offering.

## Required Permissions
- `college_admission_cycle.view`
- `college_admission_cycle.create`
- `college_admission_cycle.update`
- `college_admission_cycle.enable`
- `college_admission_cycle.disable`

## Create / Edit Fields
- Program Offering — searchable; ACTIVE offerings only
- Cycle Name
- Cycle Code
- Application Start / End
- Admission Start / End
- Remarks

Do not ask the user to independently select Academic Session, Degree Level, Program or Curriculum here. Those are inherited from Program Offering and should be shown as read-only context.

## Searchable Offering Pattern
Each Program Offering option should expose enough searchable context to distinguish large datasets:
- Program name/code
- Academic Session name/code
- Curriculum name/code where available
- Current-session indicator
- Intake readiness/capacity where available

## Lifecycle
- Create -> `INACTIVE`
- Activate -> `ACTIVE`
- Deactivate -> `INACTIVE`
- Close -> `CLOSED`

## Activation Gate
Activation must fail unless the selected Program Offering itself is ACTIVE and contains at least one:
`ACTIVE Intake -> effective Admission Seat Bucket -> ACTIVE Merit / Roster / Selection Rule`.

Reservation is not globally mandatory. If a Reservation Plan exists for the bucket consumed by a Selection Rule, that Reservation Plan must be ACTIVE.

## Safety
- Inactive College cannot mutate Admission Cycle setup.
- Cross-College Program Offering IDs are rejected.
- Cycle dates must remain inside the Academic Session inherited from Program Offering.
- An ACTIVE cycle cannot change Program Offering until deactivated.
- Closed cycles cannot be edited or reopened.
- Candidate selection and seat consumption are not performed on this page.

## Downstream Contract
Applications selected under a cycle inherit its exact Program Offering. Application UI must not ask for another Program Offering; it may only present eligible Admission Seat Buckets/specializations inside the cycle's Program Offering.
