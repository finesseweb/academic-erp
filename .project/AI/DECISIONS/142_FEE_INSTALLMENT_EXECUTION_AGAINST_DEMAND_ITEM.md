# ADR 142 — Fee Installment Execution Against Fee Demand Item

**Date:** 2026-09-08  
**Status:** Implemented — QA Pending

## Decision
`Installment Allowed` remains a Fee Structure / billing-period policy snapshot. It is not redefined downstream. Installment execution is created only against an existing Fee Demand Item whose historical snapshot has `installment_allowed = true`.

An installment schedule:
- belongs to the exact Fee Demand and Fee Demand Item;
- contains ordered installment number, amount and due date;
- must contain at least 2 installments;
- must total exactly the current net payable amount of that Fee Demand Item (gross item amount minus APPROVED Student Benefit allocation on that item);
- does not mutate Fee Demand gross amount or Fee Demand Item amount;
- cannot be created for a cancelled demand or an item where installments were not allowed;
- cannot be replaced after payment collection has started;
- may be replaced before collection; the previous ACTIVE rows are retained as CANCELLED audit history and a new ACTIVE schedule is created;
- is permission controlled through `college_fee_installment.view/manage`.

The project DatePicker is used for due dates. Due dates must be chronological. Late Fine is deliberately not calculated in this ADR; Late Fine Rules consume installment/demand due dates in the next dedicated policy implementation.

## Financial invariant
Installments schedule **when** an already-existing liability may be paid. They do not create, reduce, increase, or split the immutable gross liability.

`Net item payable = Gross Fee Demand Item - Approved Benefit adjustment on that item`

Payment Collection will later allocate receipts against the applicable demand/installment while Student Fee Ledger remains the authoritative financial trail.

## Test Data Cleanup
Installment schedules are explicitly exposed under Test Data Cleanup → Fee Management → Installment Schedules. Cleanup removes the complete test schedule for a Fee Demand Item, not an arbitrary single installment line. This follows the standing rule that every module creating QA transactional data must add its cleanup coverage when implemented.

## QA
1. Open an ACTIVE Fee Demand containing one item with `Installment Allowed = Yes` and one with `No`.
2. Confirm only the allowed item offers **Set Installments**.
3. Create 2+ installments whose total exactly equals the current net item payable; save must succeed.
4. Confirm Fee Demand gross, Fee Demand Item gross and Student Benefit adjustment remain unchanged.
5. Confirm saved installment number, amount and due date are visible after reload.
6. Try a total lower/higher than net payable; server must reject it.
7. Try non-chronological dates; server must reject it.
8. Replace the schedule before any payment; new schedule becomes ACTIVE and previous rows remain CANCELLED in DB/audit history.
9. Verify RBAC blocks unauthorized schedule management.
10. Test Data Cleanup → Fee Management → Installment Schedules must remove the QA schedule as one logical schedule and then allow the parent Fee Demand to be cleaned when no other financial dependency remains.

## 2026-09-08 Scope correction — Common/Bulk schedule is required
Individual student scheduling alone is not sufficient for ERP operation. ADR 142 therefore includes a common/bulk execution path in the same module, not as a later enhancement.

- The existing Fee Structure / period snapshot `Installment Allowed = Yes` remains the only eligibility switch; no duplicate installment-enable configuration is introduced.
- Bulk scope follows the selected academic hierarchy/context: College → Program Offering (Degree Level → Degree → Programme) → billing purpose/basis/period → installment-enabled Fee Head → student demand items. Student rows expose Admission Academic Preference discipline for controlled selection.
- The operator previews eligible student demand items before applying. Eligible students are selected by default, with discipline-group and individual deselection controls.
- A common bulk plan is percentage-based (total exactly 100%) plus due dates. This is deliberate because APPROVED Student Benefits can make net payable differ by student; each student's monetary installments are calculated from that student's current net item payable, with the final installment absorbing rounding remainder.
- Existing ACTIVE schedules may be replaced only before collection starts; prior rows are CANCELLED for audit. Any demand with collection already started is blocked/skipped from bulk replacement.
- Individual schedule management remains available as a controlled exception/override before collection.
- Gross Fee Demand and gross Fee Demand Item amounts remain immutable.
- Bulk application re-resolves the authoritative scoped items server-side and does not trust client-selected IDs outside that scope.
- Audit event `FEE_INSTALLMENT_BULK_SCHEDULE_SET` records each affected demand item; Test Data Cleanup continues to remove QA installment schedules and their derived test-only schedule history as applicable.

## QA UI refinement — large bulk cohorts (2026-09-08)
- The common installment schedule editor is rendered before discipline/student selection so schedule controls remain immediately visible even for large cohorts.
- Discipline groups are collapsed by default after preview load and after changing the installment-enabled fee head.
- Student rows are rendered only when an operator explicitly expands a discipline.
- Discipline-level selection remains available while collapsed; expanding is required only for reviewing or changing individual student selection.
- This is a presentation/scalability refinement only. Eligibility, default controlled selection, percentage validation, server-side revalidation and financial rules are unchanged.

## Benefit-after-installment integration — ADR 143
Installment Scheduling is not complete if a later approved Student Benefit leaves an older schedule total unchanged. ADR 143 therefore forms part of this module's QA gate: manual approval exposes Proportional / Next Unpaid First / Custom student-specific adjustment, automatic benefits use Proportional, paid history is immutable, and approved-benefit removal reverses the installment effect without rewriting gross liability.
