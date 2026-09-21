# Phase 13 — Course Offering delivery navigation refinement
Date: 2026-09-20
Status: IMPLEMENTED — OWNER QA REQUIRED

## Change
- Added Session -> Program Offering -> Batch -> Discipline delivery filters.
- Current Academic Session is the default; first applicable Program Offering and Batch are defaulted.
- Replaced the flat delivery list with Curriculum-driven Discipline -> Term/Semester -> optional Specialization -> Course grouping.
- Specialization is read from existing Curriculum Course Mapping / Academic Discipline data; no new academic entity or Course Offering column was introduced.
- Added Semester countable-credit totals and Discipline countable-credit summaries using existing Curriculum credit/counting definitions.
- Existing activate/deactivate actions remain unchanged.

## Architecture
No schema or hierarchy change. Existing University Curriculum remains academic authority and Course Offering remains `Batch + Curriculum Course Mapping`.
