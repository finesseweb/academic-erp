# Current Implementation State Patch — Admission Form College Mapping

- Stage 1 remains `OWNER_QA_REQUIRED`.
- University Base templates are definition/governance objects.
- College users with `college_admission_form.map` can map an ACTIVE University Base or ACTIVE College Extension to their own Program Offering + Admission Cycle.
- `Allow College Override = No` blocks College structural extension/editing only; it does not block mapping/usage.
- College mappings always persist `college_id` for affiliated-College operations.
- Degree Level, Degree and Program mapping columns are derived from Program Offering rather than manually entered.
- Mapping can be removed from the College Form Setup UI; removal inactivates the mapping for history/audit safety.
- Existing legacy mappings with null College ownership are repaired where Program Offering or Admission Cycle identifies the College.
- Application Entry continues to resolve only ACTIVE mappings + ACTIVE templates.

Next workflow remains: complete Stage 1 QA → return to Interview QA → Merit / Roster Generation.
