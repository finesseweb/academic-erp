# Curriculum Manage Structure — Curriculum Slots

## Status
Phase 1: IMPLEMENTED  
Phase 2: IMPLEMENTED

## Purpose
Defines ordered Curriculum Slots inside one existing Term / Semester.

A Slot is curriculum-specific. It is not a global reusable Slot Master.

## Navigation
`Academic Setup -> Curriculum -> Curriculum Header -> Manage Structure -> Terms / Semesters -> Slots`

## Complete Slot Fields
### Structural identity
- Course Category — required; existing ACTIVE same-University master
- Slot Name — required; curriculum/term-specific label
- Display Order — required; unique inside the selected Term / Semester

### Academic behavior
- Course Type — required; existing ACTIVE same-University master
- Selection Rule — required: `MANDATORY` or `CHOICE`
- Minimum Selection — required only when Selection Rule is `CHOICE`
- Maximum Selection — required only when Selection Rule is `CHOICE`; must be >= Minimum Selection
- Status — `ACTIVE|INACTIVE`

## Course Type Meaning
Course Type is a Slot-level constraint for the later Course / Paper Mapping stage.

When courses are mapped later, mapped courses must be compatible with the Slot's Course Type. The Slot does not create a new Course Type; it reuses the existing University Course Type master.

## Mandatory / Choice Meaning
### Mandatory
Every course mapped into the Slot is required under that Slot.

For `MANDATORY` Slots:
- `min_selection` is not stored
- `max_selection` is not stored

### Choice
The Slot represents a choice group from which the applicable student must select a number of mapped courses.

For `CHOICE` Slots:
- Minimum Selection is required
- Maximum Selection is required
- Maximum Selection cannot be less than Minimum Selection

Example:
`Elective Group 1` -> Choice -> Min 1 -> Max 2

The actual available courses are not defined here. They are added in the later Course / Paper Mapping milestone.

## Credit Rule
No Credit field exists on Curriculum Slot.

Credits have not been introduced at this stage of the frozen implementation sequence and must not be duplicated into Slot configuration.

## Reuse / Copy-Clone Rule
Course Category and Course Type are reusable master data.

Actual Curriculum Slot records remain specific to a Curriculum Term / Semester and are not shared by ID between curriculum versions.

When reuse is needed, future Copy / Clone Structure creates independent Term/Slot records for the target Curriculum version while preserving the source structure.

## Permissions
- View: `curriculum.view`
- Create/update/status: `curriculum.update`
- No new permission family

## Lifecycle
- DRAFT Curriculum: Slot structure may be changed
- ACTIVE / RETIRED Curriculum: read-only
- No hard delete
- ACTIVE / INACTIVE Slot status only

## Audit
- `CURRICULUM_SLOT_CREATED`
- `CURRICULUM_SLOT_UPDATED`
- `CURRICULUM_SLOT_STATUS_CHANGED`

## Next Milestone
Course / Paper Mapping.

The next milestone will map existing Course / Subject Master records into the selected Slot. It must validate Curriculum scope, Term/Slot scope, status, and Course Type compatibility.

## Not Implemented Yet
- Course / Paper Mapping
- Mapping Display Order
- Credit totals
- Curriculum total credits
- Structure validation
- Copy / Clone execution UI

## Course / Paper Mapping Child — 2026-08-22
Course / Paper Mapping is now implemented as the contextual `Courses` action on each Slot.

It reuses Course / Subject Master and filters/enforces same-University, ACTIVE, Course Category and Course Type compatibility.

Next remaining milestone: Mapping Display Order.


## Credits — Implemented 2026-08-22
Credits are now restored to the Curriculum Slot according to the University ERP hierarchy.

- Credits are curriculum/version-specific.
- Credits do not belong to reusable Course Master.
- Credits are not duplicated into Course Mapping.
- New/edited Slots require a Credit value.
- Historical pre-Credit rows may remain null until edited.
- Credit Summary is the next implementation.
