# ADR 032 — Admission Form Conditions May Use Any Existing Eligible Field

## Status
Accepted — 2026-08-27

## Decision
Admission Form answer-based conditions are not limited to Caste/EWS fields, a specific panel, or an earlier step.

Any already-created non-file field in the same University template may be selected as a condition source. A College extension may select any already-created non-file field from its inherited University base or from the College extension itself.

FILE and IMAGE fields remain ineligible as condition sources.

Condition relationships are stored by field ID, not by label, so labels can be edited without breaking the dependency.

The builder groups eligible parent fields by step for readability, but step order does not restrict which existing field can be selected.

## Rationale
The form builder is intended to be generic. Users may build arbitrary parent/child logic such as Category → Certificate, Employment Status → Employer Details, Completed Session → Marksheet, or any other institution-defined relationship. Hard-coding specific field families or earlier-step-only rules makes the builder incomplete.

## Safety
A newly-created child cannot self-reference because it does not yet exist when its condition is created. FILE/IMAGE parents remain blocked. If condition editing is expanded to allow changing dependencies between existing fields, cycle detection must be enforced before save.
