# ADR 163 — Academic Period hierarchical searchable selector

Date: 2026-09-09
Status: Implemented — QA pending

## Context
ADR 161 exposed every Curriculum Term in one long Academic Period dropdown. The option label included long Curriculum revision codes, making the modal hard to scan and unsuitable for universities with many Curricula.

## Decision
The University Academic Calendar `Add Academic Period` UI uses a two-step dependent selection while preserving the exact `curriculum_term_id` backend contract:

1. Search Curriculum by Curriculum/Programme name or code.
2. Select Curriculum from the filtered list.
3. Select Academic Period (Semester/Year) from the selected Curriculum only.
4. Save the exact selected `curriculum_term_id` through a hidden form field.

Already configured Curriculum Terms are excluded from the selectable periods. A Curriculum with no remaining unconfigured Terms is excluded from the Curriculum selector.

Internal revision-chain codes are not displayed in the user-facing selection label. They remain available only as searchable metadata where useful.

## Invariants
- Semester/Year identity remains owned by Curriculum; Academic Calendar does not create a duplicate master.
- No schema, RBAC, route, University/College governance, Fee Due Date, or Late Fine business-rule change.
- Existing project UI components are used; no external CSS or third-party searchable-select dependency is introduced.
- Editing an existing Academic Period continues to edit dates/governance only; its Curriculum Term identity remains immutable.

## QA
- Search by Curriculum name and Programme name/code.
- Select Curriculum and verify only its unconfigured Semester/Year Terms appear.
- Verify switching Curriculum clears the previously selected Term.
- Verify Save is disabled until a Term is selected.
- Verify already configured Terms cannot be selected again.
- Verify the saved Academic Period is still linked to the exact expected Curriculum Term.
