# Intake / Seat Capacity Individual Cleanup — 2026-08-25

- Added dedicated `Intake / Seat Capacity` tab to Test Data Cleanup.
- Each Intake row shows allocation count and downstream blocking references.
- Individual cleanup deletes Specialization child allocations first, then Discipline allocations, then Intake header.
- Program Offering cleanup remains blocked while an Intake exists.
- Future Reservation/Admission/Student references block Intake cleanup until dependent test data is cleaned.
