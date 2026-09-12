# Intake Lifecycle Protected Role Repair — 2026-08-25

- Fixed missing Intake Activate/Deactivate action for protected COLLEGE_ADMIN assignments.
- `college_program_intake.enable` and `.disable` are mandatory protected-role capabilities.
- Permission rows and COLLEGE_ADMIN role-permission grants are forward-synchronized.
- Controller has a narrowly-scoped protected-role fallback for enable/disable when role-permission synchronization is incomplete.
- Fallback requires an ACTIVE, effective COLLEGE_ADMIN assignment for the exact College scope.
- Custom roles still depend entirely on their explicitly granted permissions.
- Backend remains authoritative; UI rendering is derived from the same authorization rule.
