# ADR 012 — College Program Offering Adoption

## Decision
College Academic Setup references University-owned academic definitions rather than duplicating them. `college_program_offerings` binds a College to one Program Template + Academic Session + approved active Curriculum.

## Reason
Program Templates and Curricula are University-governed definitions. A College's responsibility is to declare what it operates, not to create competing academic masters.

## Lifecycle
New offerings start `INACTIVE`. Explicit activation makes them eligible for downstream College modules. Deactivation is non-destructive.

## Uniqueness
Only one offering per College + Program Template + Academic Session. Curriculum is the selected approved version for that offering.

## Future Dependencies
Intake / Seat Capacity references active College Program Offerings directly. Reservation / Quota then consumes the active Intake seat bucket; Batches, Sections and College Academic Calendar must continue to respect this College operational hierarchy where applicable. They must not bypass this layer by directly selecting arbitrary University Program Templates.

## Protected Role Synchronization
`SUPER_ADMIN` and `COLLEGE_ADMIN` are protected system roles. Program Offering permissions are synchronized into both roles by migrations.

For `COLLEGE_ADMIN`, the five Program Offering permissions are mandatory operational permissions and must display as selected/read-only in the protected Role Permission Matrix. They also remain College-delegable so the College Administrator can grant an allowed subset to College-owned custom roles without changing the protected system-role baseline.


## Current Session Default Rule — 2026-08-25
For a NEW College Program Offering:
- the University's Academic Session with `is_current = true` and `status = ACTIVE` is preselected automatically;
- other eligible `PLANNED` / `ACTIVE` sessions remain selectable;
- changing which Academic Session is Current later does not rewrite an existing Offering.

`Current` is therefore a setup/default context, not a historical migration instruction.

## Current Curriculum Selection Rule — 2026-08-25
A NEW or UPDATED College Program Offering may select only the CURRENT approved active Curriculum for the chosen Program Template + Academic Session.

The project already derives Curriculum currentness from the amendment/version chain:
- Current = `ACTIVE + APPROVED` and no direct `APPROVED` amendment child.
- Previous = an approved version that has a direct approved amendment child.

Do NOT add a second mutable `is_current_version` flag to `curricula`; that would conflict with ADR 007.

Historical safety:
- if an existing Offering points to V1.0 and V1.1 later becomes Current, the existing Offering remains on V1.0;
- no silent auto-upgrade or FK rewrite is allowed;
- future migration/adoption to a newer Curriculum must be an explicit governed operation if/when such a workflow is introduced.


## Intake Granularity Refinement — 2026-08-25
The Intake milestone supports both Program-only capacity and structured Discipline/Specialization capacity. This is implemented beneath Program Offering and does not duplicate University Disciplines/Specializations.
