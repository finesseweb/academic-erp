# ADR 026 — Admission Form Current Curriculum and College Override Governance

## Decision
Admission Form Setup must follow the same University-first governance principle already used by the Academic Calendar.

### Current Curriculum contract
Whenever Admission Form field applicability offers a Curriculum selector, the dropdown may contain only the current approved ACTIVE Curriculum version.
A Curriculum is current when:
- `lifecycle_status = ACTIVE`
- `approval_status = APPROVED`
- it has no approved amendment/successor (`curricula.parent_curriculum_id` chain)

Superseded Curriculum versions remain historical records and must not be selectable for new Form Setup applicability rules. Backend validation repeats the same rule so a stale/forged Curriculum ID cannot bypass the UI.

### University → College override contract
Each University Admission Form Template carries `allow_college_override`.
- `false` (default): College consumes the University form but cannot create a College extension from it.
- `true`: an authorized College user may create a College-owned extension inheriting that University base.

This intentionally mirrors the Academic Calendar's explicit `allow_college_override` governance pattern.

College extension creation is never enabled merely because a user has an RBAC permission. Both are required:
1. the user has the existing College-scoped Admission Form permission; and
2. the ACTIVE University base explicitly has `allow_college_override = true`.

University base steps/fields remain protected system-owned configuration. College extensions append College-specific steps/fields and may add narrower academic applicability. They do not mutate the historical University base record.

## Lifecycle safety
University cannot turn College override OFF while an ACTIVE College extension still inherits the template. The College extension must first be retired/remapped. This prevents silently breaking an active application form configuration.

## Existing-template migration
For templates created before this explicit boolean existed:
- University templates whose previous governance mode was `UNIVERSITY_BASE_COLLEGE_EXTENSION` migrate to `allow_college_override = true`.
- all others default to false.

## Why
- keeps Admission Form governance consistent with an existing ERP pattern instead of inventing a parallel control model;
- preserves University authority while allowing controlled College customization;
- prevents a superseded Curriculum version from being selected after an amendment;
- preserves end-to-end links from Program Offering → current Curriculum → Application Form applicability.
