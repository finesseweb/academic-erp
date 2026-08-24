# 007 — Curriculum Amendment Versioning

## Status
OWNER APPROVED — IMPLEMENTED IN REPLACEMENT PACKAGE (2026-08-24)

## Decision
An `ACTIVE / APPROVED` Curriculum is immutable. Urgent or later academic changes must not overwrite the approved record.

A change to an approved Curriculum is made through **Amend Curriculum**:

`CURRENT APPROVED VERSION -> AMEND -> NEW DRAFT VERSION -> EDIT -> VALIDATE -> APPROVAL -> NEW CURRENT APPROVED VERSION`

## Rules
- Amendment is allowed only from the current `ACTIVE / APPROVED` version.
- The amendment stays in the same University, Program Template and Academic Session.
- The complete current structure is copied into a new independent DRAFT Curriculum row.
- Terms/Semesters, Slots, Credits, selection rules and Course Mappings can then be changed in the new DRAFT.
- The approved source row and its structure are never mutated.
- The amendment must pass the existing Structure Validation checkpoint and the existing Academic Approval workflow.
- Only one non-retired direct amendment may exist from a source version at a time; this prevents version forks.
- A new approved amendment makes the source a **Previous** approved version. The previous version is retained as history and is not disabled/deleted merely because a later version is approved.
- Current/Previous is derived from the version relationship and approval state; no mutable `is_current` flag is required.
- `Add Curriculum` remains the independent/new Curriculum creation path. `Clone Structure` remains independent reuse. `Amend Curriculum` is the linked next-version path.

## Subject Replacement Rule
If an academic subject really changes, for example `English Literature -> Linguistics`, do not rename the historical Course Master row. Create/use the proper Linguistics Course Master record and change the Course Mapping only in the amendment version.

## Data Model
`curricula.parent_curriculum_id -> curricula.id`

Additional amendment metadata:
- `revision_type`
- `revision_reason`
- `revision_effective_from`

The normal `version` field remains the Curriculum version identifier.

## Current Version Rule
For an approved active Curriculum version:
- **Current** = it has no direct `APPROVED` amendment child.
- **Previous** = it has a direct `APPROVED` amendment child.

Because amendments are forced into a single chain, this produces:

`V1.0 -> V1.1 -> V1.2 -> ...`

while an unapproved V1.2 does not replace V1.1 yet.
