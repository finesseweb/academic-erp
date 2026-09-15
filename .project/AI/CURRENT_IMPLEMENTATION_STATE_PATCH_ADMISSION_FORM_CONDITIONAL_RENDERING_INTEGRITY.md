# Current Implementation State Patch — Admission Form Conditional Rendering Integrity

**Implemented — 2026-09-01**

- Restored ADR 032 in University and College builders: answer-condition parents can be any existing non-file field across the template hierarchy, not only same/earlier Steps.
- Edit Field now exposes the current answer condition and can change/remove it.
- Added server-side self-reference and circular-dependency prevention for edited conditions.
- Academic applicability is now resolved before answer-condition evaluation in backend normalization.
- Effective form payload recursively removes conditional children whose source field is absent after applicability resolution, preventing dangling runtime dependencies.
- Public Applicant and internal College Application Entry continue to use the same canonical condition operators/values.
- No migration required.

See ADR 073.
