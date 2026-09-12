# ADR 144 — Student Operational Register Context and Scalability

**Date:** 2026-09-09  
**Status:** Implemented for Student Benefit Register; project-wide pattern established for future student operational screens

## Context
Student-facing operational pages will grow every academic session. Rendering every student/benefit as a fully expanded card does not scale and makes approval work difficult. A single universal `Batch` filter is also incorrect because the canonical lifecycle assigns Programme / Batch / Semester during Student Information & Enrollment, after Admission Confirmation. Pre-enrollment finance/admission work may therefore involve a confirmed admission that has no assigned Batch yet.

## Decision
Student operational registers must use a consistent, lifecycle-aware academic context and compact register/detail pattern.

### Common register rules
- Academic Session is the first context selector.
- The University's `is_current = true` Academic Session is selected automatically when the page opens.
- The operator may explicitly switch to a historical Session.
- Remaining filters are optional and independently usable; `All` is permitted unless the specific workflow requires a narrower scope.
- Search and filtering are server-side for scalable datasets.
- Registers use server-side pagination; do not preload an arbitrary large cohort into the browser.
- The compact register shows only the fields required to identify and triage a record. Full workflow/actions belong in a View/detail area.

### Lifecycle-aware Batch rule
- `Batch` must **not** be forced as a universal student filter.
- Pre-enrollment / confirmed-admission workflows use Admission + Programme Offering + Session context because Batch may not exist yet.
- Post-enrollment workflows may use the student's assigned Batch/Section because those values are lifecycle facts by then.
- Future modules must choose academic filters from the lifecycle state guaranteed by that module, not from convenience.

## Student Benefit Register implementation
The Student Benefit Register now uses:

`Current Session (default) → Programme Offering → Billing Period → Scheme → Status → Search`

- Search covers Student Name, Application No., Admission No., Demand No. and Scheme.
- Register rows are compact and paginated at 25/50/100 rows per page.
- `View` opens the existing benefit detail/approval controls for only the selected record.
- Benefit approval data, reservation-category snapshot, sanction controls and installment-adjustment controls remain available in the detail area.
- No Batch filter is shown because Student Benefits can operate before enrollment/batch assignment.

## Installment adjustment wording/UI consistency
- Internal mode `NEXT_UNPAID_FIRST` remains unchanged for database/API compatibility.
- UI label is `Apply to Next Installment First`.
- `Custom Distribution` shows New Payable, Allocated and Remaining while amounts are entered.

## Project-wide consistency requirement
When a new student-related register/create/select workflow is implemented, reuse this standard:
1. default to Current Academic Session;
2. derive the remaining academic selectors from the student's guaranteed lifecycle state;
3. use compact register + View/detail for large operational datasets;
4. use server-side search/filter/pagination;
5. never introduce Batch as a required pre-enrollment context.

## Test Data Cleanup
This is presentation/query scoping only and creates no new master or transactional data. Existing Student Benefit and Installment Schedule cleanup coverage remains authoritative.

## QA
1. Open Student Benefits with no query parameters: Current Session must be selected automatically.
2. Verify only benefits from the selected Session are returned.
3. Change to an older Session and verify historical records can be filtered.
4. Verify Programme Offering, Billing Period, Scheme, Status and Search filters.
5. Verify pagination at 25/50/100 rows and that the browser does not receive the full historical register.
6. Verify `View` opens the selected record's full approval/detail controls only.
7. Verify `Apply to Next Installment First` retains `NEXT_UNPAID_FIRST` behavior.
8. Verify Custom Distribution displays New Payable / Allocated / Remaining and backend still rejects invalid totals.
