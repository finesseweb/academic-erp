# Intake Allocation Schema Alignment — 2026-08-25

Existing installations may have created `college_program_intake_allocations`
before hierarchical capacity fields were introduced.

Forward corrective migrations now guarantee:
- `seat_scope_type`
- `parent_allocation_id`

without dropping the Intake tables.

Existing allocation rows:
- no specialization -> `DISCIPLINE`
- existing specialization -> `ADMISSION_SPECIALIZATION`

The final business model remains:
Program -> Discipline -> optional Specialization child capacity.
