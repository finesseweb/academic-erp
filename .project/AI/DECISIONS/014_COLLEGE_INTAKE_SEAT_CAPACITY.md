# ADR 014 — Hierarchical College Intake / Seat Capacity

## Status
IMPLEMENTED_IN_REPLACEMENT_PACKAGE / OWNER_QA_REQUIRED

## Decision
Sanctioned admission capacity is hierarchical:

`Program Offering -> Program Capacity -> Discipline Capacity -> optional Specialization Capacity`

The Intake header stores Program capacity.

Allocation mode:
- `PROGRAM` — Program capacity is the only admission bucket.
- `DISCIPLINE` — Program capacity is split into Discipline capacities. Each Discipline may optionally contain Specialization capacity child rows.

There is no separate `ADMISSION_SPECIALIZATION` Intake mode.

## Critical Student Rule
Specialization is optional at student level unless the institution's later admission rule explicitly requires otherwise.

A Discipline capacity is the parent ceiling.

Example:
- BA Program capacity = 120
- English Discipline = 60
  - Literature Specialization = 20
  - Linguistics Specialization = 20
  - General English remaining = 20
- History Discipline = 60

The Program total is 120, not 160.
The Specialization rows are children inside English's 60 seats and are never added again to Program capacity.

## Capacity Equations
For an ACTIVE Discipline-wise Intake:

`sum(Discipline capacities) = Program approved capacity`

For each Discipline:

`sum(Specialization capacities) <= Discipline capacity`

The remainder is automatically available as General Discipline capacity:

`General Discipline seats = Discipline capacity - sum(Specialization capacities)`

Specialization total is NOT required to equal Discipline capacity.

## Student Lifecycle Contract
Future admission stores:
- `program_offering_id`
- `intake_id`
- `discipline_allocation_id` when Discipline-wise
- `specialization_allocation_id` nullable

Normal Discipline student:
`discipline_allocation_id = English`
`specialization_allocation_id = NULL`

Specialization student:
`discipline_allocation_id = English`
`specialization_allocation_id = Literature`

Both consume one seat from the English parent Discipline capacity.
A specialization student also consumes one seat from the Literature child capacity.

## Governance
Discipline/Specialization options must come from the University Program Template mappings.
College never creates academic masters from Intake.

## Historical Safety
Admission seat references are historical and must not be silently rewritten when capacities are later changed.
Future downstream Admission/Student records must introduce guards preventing unsafe capacity reduction below already-consumed seats.
