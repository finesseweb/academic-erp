# Curriculum Credits — Slot Credit Binding

## Status
IMPLEMENTED — Slot Credit Binding

## Source-of-truth
The University ERP hierarchy defines `Credits` inside `Curriculum Slots`, before Mandatory / Choice and before Course Mapping, followed later by `Credit Summary` and `Curriculum Validation`.

Therefore Credits are stored on `curriculum_slots`, not on reusable Course / Paper Master and not duplicated on individual Course Mapping rows.

## Meaning
`curriculum_slots.credits` is the curriculum/version-specific credit value configured for that Slot.

This preserves Course / Paper Master as reusable academic master data while allowing different Curriculum versions to assign different credit structures.

## Fields
- Credits — required for new/updated Slot
- decimal value, 0.00 to 99.99

The database column is nullable during upgrade only so existing Slot rows migrate safely. Old Slots showing no Credit must be edited and assigned Credits before final credit-aware validation.

## Lifecycle
- DRAFT Curriculum: Credits may be set/edited
- ACTIVE Curriculum: read-only
- RETIRED Curriculum: read-only

Credits use the existing Curriculum Slot update permission and audit trail:
- permission: `curriculum.update`
- audit: existing `CURRICULUM_SLOT_CREATED` / `CURRICULUM_SLOT_UPDATED`

## Course Master rule
Do not add Curriculum Credits directly to reusable Course / Paper Master in this milestone.

## Course Mapping rule
Do not duplicate Slot Credit into every Course Mapping row.

Course Mapping continues to define:
- Discipline
- optional Specialization
- Course / Paper
- Display Order
- Status

## Choice Slots
This milestone stores the canonical Slot Credit only.

How Choice Min/Max affects total Credit aggregation is handled explicitly in the next `Credit Summary` milestone so summary semantics are not hidden inside Slot creation.

## Next implementation order
1. Slot Credit Binding — IMPLEMENTED
2. Credit Summary — NEXT
3. Final Credit-aware Curriculum Validation
4. Copy / Clone Structure

Copy / Clone is intentionally after final Credit implementation so Clone is built once against the complete Curriculum structure.
