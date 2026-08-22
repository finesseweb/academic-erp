# Curriculum Delete Rules

## Status
IMPLEMENTED FOR CURRENT CURRICULUM STRUCTURE

Delete exists to correct accidental setup entries before a Curriculum becomes operational.

## Core Rule
Delete is available only when:
1. Curriculum lifecycle is `DRAFT`.
2. User has `curriculum.update`.
3. Curriculum has not been assigned to a Student Group / Cohort.

The Student Group assignment module is not implemented yet. Therefore the current backend enforces DRAFT now; when the canonical assignment table is introduced, its existence check MUST be added to `CurriculumStructureDeleteService::assertDraftAndUnassigned()` before assignment goes live.

## Delete availability

### Entire Curriculum
DRAFT + unassigned:
- Edit
- Clone Structure
- Delete

Delete removes the complete Curriculum-owned hierarchy:
Curriculum -> Terms / Semesters -> Slots -> Course / Paper Mappings.

The UI requires a strong irreversible confirmation: the user must type the exact Curriculum Code before deletion is submitted.

ACTIVE / RETIRED / assigned:
- no Delete
- historical/operational Curriculum must remain intact
- use Retire, Clone Structure or a new Curriculum version instead

The backend always re-checks lifecycle safety; UI visibility alone is never treated as protection.


### Term / Semester
DRAFT + unassigned:
- Edit
- Clone Semester
- Set Active/Inactive
- Delete

Delete cascades intentionally through application service:
Term -> Slots -> Course/Paper Mappings.

ACTIVE / RETIRED / assigned:
- no Delete
- no structural Edit
- historical structure must remain intact.

### Curriculum Slot
DRAFT + unassigned:
- Edit
- Clone Slot
- Set Active/Inactive
- Delete

Delete removes:
Slot -> Course/Paper Mappings.

### Course / Paper Mapping
DRAFT + unassigned:
- Edit
- Set Active/Inactive
- Delete

Delete removes only the selected mapping.

## Future assignment locking
When Curriculum Assignment is implemented:
`Curriculum -> Academic Session/Batch/Program/Student Group`

Once at least one Student Group/Cohort is assigned:
- Curriculum structure is operational.
- Entire Curriculum Delete = blocked
- Term Delete = blocked
- Slot Delete = blocked
- Course Mapping Delete = blocked
- destructive structural updates = blocked
- corrections must use versioning / clone/new Curriculum version rather than deleting history.

This rule must be enforced both in UI and backend.

## Audit
Hard deletes are audit logged before/at deletion using:
- CURRICULUM_DELETED
- CURRICULUM_TERM_DELETED
- CURRICULUM_SLOT_DELETED
- CURRICULUM_COURSE_MAPPING_DELETED

## Why no database migration now?
No assignment table is invented in this change. The assignment guard is documented and centralized so it can be connected to the real canonical assignment table when that module is implemented.
