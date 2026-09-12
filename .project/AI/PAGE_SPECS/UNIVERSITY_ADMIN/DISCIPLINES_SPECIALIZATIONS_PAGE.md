# Disciplines / Specializations

## Status
Implementation: IMPLEMENTED. Review: PENDING_REVIEW. Updated 2026-08-21.

University-owned hierarchical subject master. `DISCIPLINE` rows are top-level; their Parent Discipline field is disabled and displays `Not applicable`. `SPECIALIZATION` rows require an active same-University Discipline parent.

A Discipline with one or more Specializations cannot be changed into a Specialization, preserving the hierarchy. Codes are unique per University.

Program Templates reuse this master without duplicating Discipline or Specialization records. A Program Template may map many top-level Disciplines, and each mapped Discipline may optionally map many of its own Specializations. The parent-child rule defined here is the canonical source used to validate those Program Template mappings.

Permissions: `discipline.view/create/update/disable`; audit events use `DISCIPLINE_*`. Shared academic-master UI provides theme, responsive, validation, pending, empty and confirmation states.
