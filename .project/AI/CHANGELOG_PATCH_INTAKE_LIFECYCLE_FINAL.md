# Intake Lifecycle Button Final Authorization Fix — 2026-08-25

- Fixed missing Activate/Deactivate rendering when Intake is otherwise activation-ready.
- UI and backend now use the same lifecycle authorization rule.
- Normal explicit College permission remains first priority.
- Effective COLLEGE_ADMIN assignment for the exact College is a protected lifecycle fallback.
- Effective SUPER_ADMIN assignment is also a lifecycle fallback.
- Fallback no longer depends on `roles.is_system_role`, avoiding installation-specific role metadata mismatch.
- Custom non-admin roles still require explicit `college_program_intake.enable/disable` permissions.
- Discipline activation readiness remains `sum(top-level Discipline capacity) = approved_capacity`.
