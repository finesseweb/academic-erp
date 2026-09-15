# Test Data Cleanup Intake Helper Correction — 2026-08-25

- Added missing Full Reset preview counters for College Program Intakes and Intake Allocations.
- Counter scope follows College -> Program Offering -> Intake hierarchy.
- Full Reset now explicitly removes specialization child allocations before parent Discipline allocations, then Intake, then Program Offering.
- No business/domain rule changed.
