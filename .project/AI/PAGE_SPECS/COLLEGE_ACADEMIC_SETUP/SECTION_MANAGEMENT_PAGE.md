# Section Management Page

## Route
`/college/{college}/sections`

## Purpose
Create and manage operational Sections below College Batches without redefining academic or admission capacity.

## Hierarchy
`College -> Program Offering -> Intake/Reservation context -> Batch -> Section -> Student Enrollment`

## Page Behavior
- Use a Batch-centric parent/child layout instead of a flat Section table.
- Each Batch remains the visual parent, but Section expansion must reuse the same compact expandable-row interaction already established in Academic Structure (Chevron + label + right-aligned summary), instead of introducing a parallel custom accordion pattern.
- Batch context shows Program, Academic Session, Curriculum and approved parent Program Intake once above the expandable Sections row.
- The expandable `Sections` row shows section totals/status counts and expands only that Batch.
- Sections are rendered as child rows/nodes only when that Batch is expanded, so inherited context is not repeated per Section and long pages are avoided.
- Users may expand/collapse each Batch independently with `Show Sections` / `Hide Sections`; Add Section remains available from the collapsed Batch header.
- Approved Intake is explicitly labelled as **Parent Program Intake**; it is shared context and must never be presented as Section capacity.
- Show summary: total Sections, ACTIVE, INACTIVE, ACTIVE Batches ready for Sections.
- `Add Section` is placed inside each eligible ACTIVE Batch card. The clicked Batch is preselected/fixed during creation, reducing wrong-parent selection risk.
- Existing INACTIVE Sections may still be moved through Edit only where backend lifecycle rules permit; ACTIVE Sections cannot change parent Batch.
- Create only from an ACTIVE Batch whose parent Program Offering and Intake remain ACTIVE.
- New Section starts INACTIVE.
- Edit Section code/name/notes.
- Activation/deactivation is separate from edit and protected by explicit lifecycle permissions.
- Section create/activation must never change Intake capacity, Reservation, Seat Allocation or Admission Confirmation.
- Section has no capacity/max-strength input or persisted field. Operational strength is derived from active enrolled Student placements; physical-room suitability is enforced by Course Delivery against `college_rooms.capacity`.

## RBAC
- `college_section.view`
- `college_section.create`
- `college_section.update`
- `college_section.enable`
- `college_section.disable`

## Parent Protection
An ACTIVE Section prevents its Batch from being deactivated. UI disables Batch deactivation when the dependency is known; backend guard remains authoritative.

## Test Data Cleanup
Section is an explicit cleanup entity. Cleanup is blocked by Student Enrollment references. Full reset deletes Sections before Batches.
