# ADR 147 — Fee Demand Compact Register Discipline + Age Context

**Status:** Implemented — QA Pending  
**Date:** 2026-09-09

## Context
ADR 145 established the compact server-paginated Fee Demand register. For large programme cohorts, Student Name and Application/Admission number alone are not enough to identify academic context quickly. Pre-enrollment Fee Demand cannot depend on Batch because Batch is assigned during Enrollment.

## Decision
1. Preserve ADR 145 admission-group compact register and server-side pagination.
2. Add **Discipline**, **Age**, and Programme to the parent admission row.
3. Discipline is read from the application's saved Admission Academic Preference, not from Batch.
4. Age is derived from application Date of Birth at display time.
5. Add an optional Discipline register filter constrained by selected Session and Programme Offering.
6. Search also recognises Discipline name/code.
7. Session remains Current by default; historical Session remains selectable.
8. Existing demand View/Details, benefit audit, installment scheduling and cancellation remain unchanged.

## Project consistency
High-volume student/finance registers should show compact identification context at parent level and defer transactional detail to expansion. Before Enrollment, use saved Admission Academic Preference; after Enrollment, modules may additionally use assigned Batch/Section where appropriate.

## Test Data Cleanup
Presentation/filter change only. No new records or cleanup rules are introduced.

## QA
- Fee Demand parent row shows correct Discipline, Age and Programme.
- Discipline filter scopes admissions correctly.
- Session change resets Offering/Discipline context safely.
- Pagination/search/status preserve Discipline filter.
- Demand financial totals and Details remain unchanged.
