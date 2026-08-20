# Disciplines / Specializations
## Status
Implementation: IMPLEMENTED. Review: PENDING_REVIEW. Implemented 2026-08-21.

University-owned hierarchical subject master. `DISCIPLINE` rows are top-level; their Parent Discipline field is disabled and displays `Not applicable`. `SPECIALIZATION` rows require an active same-University discipline parent. A Discipline with one or more Specializations cannot be changed into a Specialization, preserving the hierarchy. Codes are unique per University. Permissions: `discipline.view/create/update/disable`; audit events use `DISCIPLINE_*`. Shared academic-master UI provides theme, responsive, validation, pending, empty and confirmation states.
