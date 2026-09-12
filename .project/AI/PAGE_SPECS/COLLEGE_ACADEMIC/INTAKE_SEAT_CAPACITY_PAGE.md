# College Intake / Seat Capacity Page

## Hierarchy
`Program Offering -> Intake -> Discipline Capacity -> optional Specialization Capacity`

## Program Mode
Example:
`BA = 120`
No child allocations.

## Discipline Mode
Example:
`BA = 120`
- English = 60
  - Literature = 20
  - Linguistics = 20
  - General English remaining = 20
- History = 60

## UI Rules
- Add Discipline Capacity creates a top-level Discipline allocation.
- Each Discipline row has `Add Specialization Capacity`.
- Specialization selector shows only ACTIVE specializations mapped under that Discipline for the Offering's Program Template.
- UI displays:
  - Discipline capacity
  - specialization allocated total
  - remaining General Discipline seats
- General remaining is derived, not stored as another allocation row.

## Activation
PROGRAM:
- approved capacity > 0
- no child allocations

DISCIPLINE:
- at least one Discipline allocation
- sum Discipline capacities = Program approved capacity
- for every Discipline, sum Specialization child capacities <= Discipline capacity

Specialization capacities may be zero, partial, or full.
They are never mandatory merely because Specializations exist.

## Student Lifecycle
Admission seat identity must preserve both levels:
- Discipline allocation required for Discipline-wise Intake
- Specialization allocation nullable

A student with no Specialization consumes only General/parent Discipline availability.
A student with a Specialization consumes the parent Discipline seat plus that child Specialization capacity.

## Future Reservation / Quota
Reservation must be designed against the actual admission seat bucket without double counting hierarchical child capacities.
The Reservation module must explicitly document whether reservation applies at Program, Discipline, or Specialization child level.
