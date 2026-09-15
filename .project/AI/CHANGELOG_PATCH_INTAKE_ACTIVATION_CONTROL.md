# Intake Activation Control / Permission Repair — 2026-08-25

- Intake lifecycle action remains a separate card action, not an Edit-form field.
- Protected SUPER_ADMIN and COLLEGE_ADMIN roles are re-synchronized with all mandatory `college_program_intake.*` permissions.
- `Activate` is visible when `college_program_intake.enable` is effective for the College scope.
- Discipline-wise activation readiness is shown in the UI.
- Activation is disabled until top-level Discipline capacities total exactly the Intake approved capacity.
- Backend activation validation remains authoritative.
- ACTIVE Intake exposes effective seat buckets to Reservation / Seat Distribution.
