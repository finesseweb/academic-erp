# Curriculum Copy / Clone Structure

## Status
IMPLEMENTED — Final Professional Clone Rules

## Clone Entire Structure
Creates a new DRAFT Curriculum and copies the complete implemented Curriculum structure:
Terms/Semesters, Slots, Credits, Mandatory/Choice rules, Min/Max selection, Course/Paper Mappings, Discipline, Specialization and display orders. All cloned rows receive new IDs. Credit Summary is derived and is not copied.

The complete source must pass Validate Structure.

## Clone Semester
Copies the selected Semester and everything below it:
Semester -> all Slots -> Credits -> selection rules -> all Course/Paper Mappings -> Discipline/Specialization -> display orders.

Target may be the same Curriculum or another existing compatible Curriculum version.
Target Curriculum must:
- be DRAFT
- belong to the same University
- use the same Program Template
- not already contain the requested Semester sequence

The target Semester does not need to exist because Semester itself is being cloned.

## Clone Slot
Copies the selected Slot and everything below it:
Slot -> Category/Type -> Credits -> Mandatory/Choice -> Min/Max -> all Course/Paper Mappings -> Discipline/Specialization -> mapping order.

Target may be the same Curriculum or another existing compatible Curriculum version.
Target Curriculum must:
- be DRAFT
- belong to the same University
- use the same Program Template

For Slot Clone, the target Term/Semester MUST already exist in the selected target Curriculum.

## Source vs Target Lifecycle
Source Curriculum may be DRAFT, ACTIVE or RETIRED because cloning reads source history.
Target Curriculum must always be DRAFT because clone changes target structure.

## Frozen Meaning
- Entire Structure Clone = whole Curriculum hierarchy.
- Semester Clone = selected Semester plus every descendant.
- Slot Clone = selected Slot plus every descendant.

## Delete / Assignment Safety
Clone never modifies source records. Existing delete policy remains: hard delete only in DRAFT and before Student Group/Cohort assignment. After assignment, preserve history and use versioning/cloning.

## Database
No new table or migration.
