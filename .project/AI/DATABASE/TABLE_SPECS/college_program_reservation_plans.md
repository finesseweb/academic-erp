# college_program_reservation_plans

One Reservation / Seat Distribution plan for one effective admission seat bucket derived from an ACTIVE Intake.

## Bucket Types
- `PROGRAM`
- `DISCIPLINE_GENERAL`
- `SPECIALIZATION`

For Discipline mode, Reservation uses leaf/effective admission buckets so parent/child capacity is never double-counted.

`DISCIPLINE_GENERAL` is derived:
`Discipline capacity - sum(Specialization child capacities)`.

Each plan stores the derived `basis_capacity` and revalidates it before activation.
