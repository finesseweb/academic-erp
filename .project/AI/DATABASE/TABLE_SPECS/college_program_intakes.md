# college_program_intakes

Header for one College Program Offering's sanctioned Program capacity.

## allocation_mode
- `PROGRAM`
- `DISCIPLINE`

## approved_capacity
Program-level sanctioned capacity.

## Rule
When allocation_mode = DISCIPLINE:
`sum(top-level Discipline allocation seat_capacity) = approved_capacity` before activation.

Specialization capacities are child rows and are not included again in Program total.
