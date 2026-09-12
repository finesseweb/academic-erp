# ADR 167 — DatePicker Dynamic Year Range Synchronisation

## Status
Implemented — QA pending.

## Context
Project DatePicker supports optional `min` and `max` boundaries. Academic Period setup now derives those boundaries dynamically after a Curriculum is selected. The DatePicker's visible month/year state previously remained on the browser's current month even when a new `min` boundary moved the allowed range to a later year. The Year Select therefore held a value that did not exist in its generated options and rendered blank.

## Decision
Keep the DatePicker view month synchronised with its effective `min` / `max` boundary.

- Initial view is clamped to the allowed month range.
- When controlled value changes, its view is clamped before rendering.
- When `min` or `max` changes dynamically, the current view is moved to the nearest allowed month if needed.
- Existing DatePicker API and styling remain unchanged.
- This is a shared component fix, so Academic Period, Fee Due Date, and any other bounded DatePicker receive the same behaviour.

## Expected QA
For a Curriculum whose valid range starts in 2027, opening a blank DatePicker in 2026 must automatically show the first allowed month/year and the Year selector must display a valid year. Changing Curriculum to another date range must immediately resynchronise the picker.

## Migration
None.
