# ADR 143 — Student Benefit Approval → Installment Adjustment Policy

**Date:** 2026-09-08  
**Status:** Implemented — QA Pending

## Context
A Fee Demand Item may already have an ACTIVE installment schedule when a Scholarship / Concession / Waiver is later approved. The approved benefit reduces the student's net payable amount, but the immutable gross Fee Demand and gross Fee Demand Item must not change. Leaving the old installment schedule unchanged would make the schedule total inconsistent with the student's new payable liability.

## Decision
Student Benefit and Installment Scheduling remain separate financial concepts but are integrated through an explicit adjustment policy at sanction time.

For a PENDING MANUAL Student Benefit that affects a Fee Demand Item with an ACTIVE installment schedule, the approver must be able to choose how the benefit changes that student's schedule:

1. **PROPORTIONAL — Recommended/default**  
   Recalculate the affected student's ACTIVE installments using the schedule's stored allocation percentages. Existing schedules created before this ADR derive their proportions from their current amounts. The final installment absorbs rounding so the schedule reconciles to the student's post-benefit net payable.

2. **NEXT_UNPAID_FIRST — UI label: Apply to Next Installment First**  
   Reduce the earliest unpaid/partially-unpaid installment first, then continue forward only if the approved benefit exceeds that installment's remaining liability. The friendlier UI label is authoritative for presentation; the persisted/internal enum remains `NEXT_UNPAID_FIRST` for compatibility.

3. **CUSTOM — UI label: Custom Distribution**  
   An authorized approver may provide the complete post-benefit installment amounts for the affected Fee Demand Item. Backend validation requires the custom schedule total to equal the post-benefit net payable and no installment may be reduced below its already-paid amount. The UI must show New Payable, Allocated and Remaining while the approver enters the distribution; backend validation remains authoritative.

If no ACTIVE installment schedule exists for the affected Fee Demand Item, Student Benefit approval proceeds normally and no installment-adjustment mode is recorded.

AUTOMATIC benefit schemes use **PROPORTIONAL** as the safe default because there is no manual approval interaction.

## Student-specific behavior
A common/bulk installment plan is only the scheduling policy source. Actual ACTIVE installment rows belong to a specific student's Fee Demand Item. Therefore a benefit sanctioned only for Amit changes only Amit's affected schedule; Ashutosh and other students sharing the same common plan remain unchanged.

Example: common plan 50% + 50%, item net ₹20,000. Amit receives an approved ₹3,000 benefit. Under PROPORTIONAL, Amit becomes ₹8,500 + ₹8,500 while another student without that benefit remains ₹10,000 + ₹10,000.

## Payment/history invariant
Already-paid money is historical fact and must never be rewritten by a later benefit. Installment rows now carry `paid_amount` so the upcoming Payment Collection + Allocation module can post collection against individual installments without changing this rule.

If a future benefit creates an overpaid/credit situation, paid history remains immutable and the excess must be resolved by the later Adjustment/Refund flow rather than rewriting receipts.

## Audit and reversal
- Student Benefit stores the chosen `installment_adjustment_mode` and an approval snapshot containing before/after installment amounts.
- `FEE_INSTALLMENT_BENEFIT_ADJUSTED` records each affected Fee Demand Item adjustment.
- Removing an APPROVED benefit also recalculates/reverses its installment effect.
- If the ACTIVE schedule still exactly matches the benefit's recorded post-approval snapshot, cancellation restores the exact pre-benefit schedule.
- If the schedule changed afterward, cancellation does not overwrite newer history; it safely recalculates the current ACTIVE schedule proportionally to the restored net payable and records `FEE_INSTALLMENT_BENEFIT_REVERSAL`.

## Financial invariants
- Gross Fee Demand is immutable.
- Gross Fee Demand Item is immutable.
- Approved Benefit changes `adjusted_amount` / net payable only.
- Installment schedule describes timing/allocation of that net payable; it does not create a second liability.
- Only the affected student's affected Fee Demand Item schedule is changed.
- Server-side validation is authoritative for every approval/reversal.

## Test Data Cleanup
No new independent master data is introduced. Student Benefit QA records and Installment Schedule QA records remain covered by the standing Test Data Cleanup rules. Cleanup must also remove their test-only installment adjustment snapshots/audit-derived schedule history when the parent QA records are cleaned, while preserving real fee/benefit master configuration.

## QA additions before Installment Scheduling PASS
1. Create a 50% + 50% ACTIVE installment schedule for two students sharing the same common plan.
2. Assign a MANUAL benefit only to Student A and approve it with **PROPORTIONAL**. Verify only Student A changes and Student B remains unchanged.
3. Example net ₹20,000 → approved benefit ₹3,000 → Student A becomes ₹8,500 + ₹8,500.
4. Repeat with **NEXT_UNPAID_FIRST**: ₹10,000 + ₹10,000 with ₹3,000 benefit should become ₹7,000 + ₹10,000 before collection.
5. Repeat with **CUSTOM** and verify a valid ₹9,000 + ₹8,000 distribution succeeds for a ₹17,000 post-benefit net; totals not equal to ₹17,000 must fail.
6. Verify Fee Demand gross/item gross do not change; only benefit adjustment, outstanding and installment amounts change.
7. Remove the approved benefit before any later schedule mutation. Verify Fee Demand adjustment reverses and the exact pre-benefit installment schedule is restored.
8. Verify an AUTOMATIC scheme uses PROPORTIONAL without requiring manual choice.
9. Future collection regression: once installment-level paid amounts exist, benefit adjustment must never reduce a row below its `paid_amount`.
