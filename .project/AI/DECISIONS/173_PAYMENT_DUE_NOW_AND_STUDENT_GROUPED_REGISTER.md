# ADR 173 — Payment Due-Now Default + Student-Grouped Collection Register

## Status
Implemented for Owner QA — 2026-09-10.

## Business reason
Payment Collection must remain flexible without allowing a cashier to alter the underlying fee liability. The high-volume register must also scale when many disciplines, degrees and students are present.

## Decisions
1. Fee Demand Item and Installment amounts remain immutable from Payment Collection.
2. Partial payment remains allowed. A cashier may collect less than the selected payable balance; this records payment only and never creates an installment or rewrites the fee.
3. Future scheduled dues are excluded from the default collection amount.
4. `Include future dues` is an explicit opt-in for advance collection.
5. Backend allocation enforces the future-due boundary using Payment Date; UI selection alone is not trusted.
6. Optional charges remain explicit opt-in and Late Fine remains separately selectable.
7. Overpayment beyond the selected payable set remains blocked atomically.
8. Open Payables is paginated and grouped by Student/Admission, not by individual Fee Demand. One student appears once in the main register.
9. `View Demands (N)` expands that student's demand rows, preserving Billing Period, Due Groups, exact Fee Head/installment detail, mandatory/optional/fine balances, status and per-demand Collect Payment action.
10. Student summary shows aggregate Principal Outstanding, Late Fine and Total Payable across the visible session's demands.
11. Search by demand still returns the owning student group; once selected, the student's session demands are shown together.

## Example
Tuition Fee ₹15,000 split into ₹7,500 due 05-Sep and ₹7,500 due 20-Sep. On 10-Sep, default principal collection is ₹7,500. The cashier may collect ₹4,000 as a partial payment. To collect the 20-Sep amount in advance, the cashier explicitly enables `Include future dues`. Original Fee Demand and installment amounts never change.

## QA
- Verify Amit Kumar appears once even with Academic Year + Semester demands.
- Expand Amit and verify all session demands appear under him.
- On 10-Sep, a 20-Sep installment must not be in default selected available amount.
- Enable `Include future dues`; selected available must increase by the future open amount.
- Post a partial amount; exact oldest eligible source allocation must occur and original fee/installment amount must remain unchanged.
- Attempt future-inclusive amount with `Include future dues` OFF; backend must reject overpayment.
- Re-run existing optional, late-fine, cleanup and RBAC tests.

## Migration
None.
