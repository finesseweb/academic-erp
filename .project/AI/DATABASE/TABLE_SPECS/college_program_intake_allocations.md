# college_program_intake_allocations

Hierarchical admission seat allocations.

## Core Columns
- `id`
- `college_program_intake_id`
- `parent_allocation_id` nullable, self FK
- `seat_scope_type` — `DISCIPLINE|ADMISSION_SPECIALIZATION`
- `discipline_id`
- `specialization_id` nullable
- `seat_capacity`
- `display_order`
- `status`
- audit fields/timestamps

## Discipline Row
- `seat_scope_type = DISCIPLINE`
- `parent_allocation_id = NULL`
- `specialization_id = NULL`
- `seat_capacity` is the Discipline's total sanctioned capacity.

## Specialization Row
- `seat_scope_type = ADMISSION_SPECIALIZATION`
- `parent_allocation_id` points to its Discipline allocation
- same `discipline_id` as parent
- `specialization_id` required
- `seat_capacity` is a child ceiling inside the Discipline capacity.

## Capacity Integrity
Program:
`sum(Discipline rows) = Intake approved_capacity`

Discipline:
`sum(Specialization child rows) <= Discipline seat_capacity`

Derived:
`general_discipline_capacity = Discipline seat_capacity - child specialization total`

Never add child Specialization capacity to Program total again.

## Student Reference
Future Admission/Student tables should store the Discipline allocation FK and nullable Specialization allocation FK so general Discipline admission remains valid.
