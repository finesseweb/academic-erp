# ADR 146 — Student Benefit Grouped Register + Discipline + Age

**Status:** Implemented — QA Pending  
**Date:** 2026-09-09

## Context
ADR 144 converted Student Benefits to a compact register, but one row per benefit still repeats the same student/application/demand when multiple schemes are assigned. This creates visual duplication and becomes confusing as benefit history grows. The register also needs enough academic identity to distinguish students in multi-discipline programmes.

## Decision
1. Student Benefit Register pagination is by **Fee Demand / admission context group**, not by individual benefit row.
2. All matching benefits for the same Fee Demand are shown under one parent row and remain collapsed by default.
3. Parent row shows Student, Application, Admission/Demand, Programme/Billing Period, **Discipline**, **Age**, benefit count, aggregate benefit amount and group status.
4. `Benefits (N)` expands a compact child table of schemes. `View` on an individual child opens the existing approval/removal/installment-adjustment detail for that exact benefit.
5. Individual benefit identity and audit history remain unchanged. Grouping is presentation/query scoping only; no benefit records are merged in the database.
6. Discipline is taken from the saved Admission Academic Preference for the application, which is the authoritative pre-enrollment discipline context.
7. Age is derived from the application's Date of Birth at display time; Date of Birth itself is not duplicated into the benefit record.
8. Added optional Discipline filter. Session remains first and Current Session remains auto-selected.
9. Batch is still not required because Student Benefits can exist before Enrollment/Batch Assignment.
10. Internal installment mode `NEXT_UNPAID_FIRST` remains unchanged; UI label remains `Apply to Next Installment First`.

## Pagination semantics
The pagination counter represents benefit groups/demands, not raw benefit rows. A demand with five benefits occupies one parent register row and expands to five child rows.

## Financial rules
No financial rule changes. Gross Fee Demand remains immutable; each APPROVED benefit remains an independent auditable adjustment.

## Test Data Cleanup
No new data is created. Existing Student Benefit and Installment Schedule cleanup coverage remains authoritative.

## QA
- Amit with two benefits on one demand appears once at parent level.
- Parent shows `2 Benefits` and aggregate sanctioned/calculated total.
- Expand shows both individual scheme rows.
- Individual View still supports pending approval, removal and installment adjustment.
- Discipline and Age display correctly from application context.
- Discipline filter, Session, Programme Offering, Billing Period, Scheme, Status, Search and 25/50/100 pagination work together.
