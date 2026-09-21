# Phase 13 — Course Offering Pre-Enrollment Choice Correction

Date: 2026-09-20

## Problem
The first curriculum-driven refinement incorrectly made CHOICE Course Offerings depend on existing ENROLLED student course choices. Course delivery planning can occur before Student Enrollment, so that dependency inverted the intended workflow.

## Correction
- Removed Student Enrollment / Student Course Choice lookup from Course Offering creation and preview.
- MANDATORY applicable Curriculum mappings are always included and locked.
- All active applicable CHOICE mappings are visible and selectable by the College before enrollment exists.
- Bulk create persists MANDATORY + selected CHOICE mappings only, skipping existing Batch + Mapping offerings.
- Credits and credit-counting remain read-only University Curriculum data.
- Responsive modal/table behavior from the previous UI correction is retained.

## Architecture impact
No schema or hierarchy change. Existing Program Offering, Curriculum, Discipline, Batch, Section and Student Enrollment structures are unchanged. ADR 209 and the Course Offerings PAGE_SPEC are synchronized. Owner QA remains required.
