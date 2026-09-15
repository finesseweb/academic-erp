# Curriculum Credit Summary

## Status
IMPLEMENTED — Derived Credit Summary

## Source-of-truth
Credits are stored on active `curriculum_slots`.

Credit Summary is derived at runtime; no separate summary table is created.

## Calculation

### Mandatory Slot
Required Credits = Slot Credits
Maximum Credits = Slot Credits

### Choice Slot
Required Credits = Slot Credits × Minimum Selection
Maximum Credits = Slot Credits × Maximum Selection

Example:
- Slot Credits = 3
- Min Selection = 1
- Max Selection = 2

Result:
- Required Credits = 3
- Maximum Credits = 6

## Term / Semester Summary
For each active Term / Semester:
- Required Credits = sum of active Slot required credits
- Maximum Credits = sum of active Slot maximum credits

## Curriculum Summary
- Curriculum Required Credits = sum of all Term required credits
- Curriculum Maximum Credits = sum of all Term maximum credits

## Missing Credits
Historical Slots created before Credit Binding may have null Credits.
Summary reports the number of active Slots with missing Credits.
Those Slots must be edited while Curriculum is DRAFT.

## Why no summary table
Credit Summary is derived from the canonical Slot structure.
This avoids duplicated totals becoming stale when Slot Credits or selection rules change.

## Lifecycle
Credit Summary is read-only and available for DRAFT / ACTIVE / RETIRED Curricula.

## Next
Final Credit-aware Curriculum Validation.

That validation must check:
- active Slot has Credits
- credit values are valid
- Choice credit semantics are calculable
- existing Phase 1 structure remains valid

After final validation:
Copy / Clone Structure.


## Implementation State — 2026-08-22
Final Credit-aware Curriculum Validation is now implemented.

Next implementation:
Copy / Clone Structure.
