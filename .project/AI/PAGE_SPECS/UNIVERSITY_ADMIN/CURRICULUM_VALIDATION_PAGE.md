# Curriculum Validation — Phase 1

## Documentation alignment
The University Academic Setup hierarchy defines:
Curriculum -> Curriculum Header -> Manage Structure -> Terms/Semesters -> Curriculum Slots -> Course Mapping -> Course Mapping Display Order -> Credit Summary -> Curriculum Validation.

Phase 1 implements non-credit structural validation now. Credit Summary and credit-based validation remain deferred until the documented Credit implementation is completed.

## Purpose
Validate the structure already entered before it is treated as a reliable source for later Copy / Clone and eventual activation workflow.

## Checks
- at least one Term / Semester
- continuous Term / Semester display order
- each Term has active Slots
- continuous active Slot display order
- every active Slot has at least one active Course Mapping
- CHOICE Slot has Minimum and Maximum Selection
- CHOICE Minimum >= 1
- CHOICE Maximum >= Minimum
- active mapped Course count >= Choice Maximum
- continuous active Course Mapping display order
- Course remains ACTIVE, same-University and matches Slot Category + Type
- Mapping has Discipline
- Discipline remains ACTIVE, same-University and allowed by Program Template
- optional Specialization remains ACTIVE, same-University, child of selected Discipline and allowed by the exact Program Template Discipline

## Lifecycle
Validation is read-only. It does not mutate Curriculum data.

DRAFT may be validated repeatedly while being built.
ACTIVE / RETIRED may also be checked for diagnostics, but editing remains governed by lifecycle rules.

## Phase 1 exclusions
No Credit validation is performed:
- no Slot Credit
- no Course Credit
- no Credit Summary
- no semester credit total
- no curriculum credit total
- no L-T-P/contact-hour validation

## Result
PASS only when there are zero ERROR issues.

## Copy / Clone
Copy / Clone remains the next approved implementation after this validation baseline. Clone should use a structurally valid source rather than silently duplicating known invalid structure.


## Implementation Correction — 2026-08-22
- Fixed invalid adjacent JSX in the Course / Paper Mapping header actions.
- `Validate Structure` and `Map Course / Paper` now render inside one valid action container.
- `Validate Structure` is read-only and visible independently of DRAFT edit state, subject to `curriculum.view`.
- `Map Course / Paper` remains DRAFT + `curriculum.update`.
- Added the actual validation result modal using the page's existing modal style; no new Dialog dependency.
- Validator now uses canonical fields `curriculum_terms.sequence_no` and `curriculum_slots.selection_mode`.
- Validation endpoint uses the same `curriculum.view` permission style as Curriculum Structure.
- No Credit/Credit Summary implementation was added.


## UI Placement Correction — 2026-08-22
`Validate Structure` is a Curriculum-wide action and is placed on the Curriculum `Manage Structure` / Terms-Semesters page.

It is intentionally not placed inside an individual Course / Paper Mapping page because that page is scoped to one Slot and would imply Slot-only validation.

Validation scope remains the full selected Curriculum:
- all active Terms / Semesters
- all active Curriculum Slots
- all applicable selection rules
- all Course / Paper Mappings
- mapping display order
- Program Template Discipline
- optional Specialization
- Course Category / Course Type compatibility

Course / Paper Mapping pages now contain only mapping-context actions such as Map, Edit, order and status.

The validation action is read-only and may be used regardless of DRAFT/ACTIVE/RETIRED lifecycle, subject to `curriculum.view`. Editing remains lifecycle-protected.


## Credit sequence correction — 2026-08-22
Phase 1 remains an interim non-credit diagnostic.

Now that Slot Credit Binding is being implemented, the remaining order is:
1. Credit Summary
2. Final credit-aware Curriculum Validation
3. Copy / Clone Structure

Clone is no longer the immediate next step.


## Terms Page Action Placement Correction — 2026-08-22
- `Validate Structure` is a Curriculum-wide action, but visually it is placed in the `Terms / Semesters` section action bar for better workflow clarity.
- It appears immediately to the left of `Add Term / Semester`.
- The Curriculum identity card remains alone in the page header.
- Each Term / Semester row retains its contextual `Slots` action regardless of DRAFT editability, because opening Slots is a view/navigation action.
- Edit and Set Inactive remain DRAFT/update-permission actions.


## Final Credit-aware Validation — 2026-08-22
Status: IMPLEMENTED

The existing Curriculum-wide `Validate Structure` action is now the final validator for the currently implemented Curriculum scope.

It validates:
- Terms / Semesters
- active Curriculum Slots
- Mandatory / Choice rules
- Minimum / Maximum Selection
- Course / Paper Mapping
- mapping display order
- Program Template Discipline
- optional Specialization
- Course Category / Course Type compatibility
- Credits on every active Slot

Credit rules:
- every active Slot must have Credits
- Credits must be numeric and non-negative
- Choice Slot Credit totals remain calculable through the already validated Min / Max rules

Credit Summary remains derived from canonical Slot Credits.

A Curriculum passes only when there are zero ERROR issues.

The validator is read-only.

## Next Implementation
Copy / Clone Structure.


## Approval Checkpoint Upgrade — 2026-08-22

The validator remains **read-only with respect to academic structure**: it never edits Terms, Slots, Credits or Course Mappings.

For Phase 5 governance, a successful validation now writes only validation metadata on the Curriculum:
- `structure_validation_hash`
- `structure_validated_at`
- `structure_validated_by`

This metadata is an approval-readiness checkpoint, not an academic structure mutation.

### Stale Validation Rule
The checkpoint fingerprint covers the Curriculum header and implemented structural children. Any relevant structural/header change makes the stored hash different from the current fingerprint. Therefore:
- previous PASS becomes stale automatically
- `Submit for Approval` disappears/is unavailable
- user must run `Validate Structure` again

Submission requires a current recorded PASS and Laravel performs one additional live validation before creating the approval request.

Audit event:
`CURRICULUM_STRUCTURE_VALIDATED`
