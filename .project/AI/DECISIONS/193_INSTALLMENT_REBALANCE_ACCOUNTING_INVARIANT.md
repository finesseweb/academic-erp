# ADR 193 — Installment Rebalance Accounting Invariant

**Status:** ACCEPTED / IMPLEMENTED / OWNER QA REQUIRED  
**Date:** 2026-09-14  
**Related:** ADR 190 Generic Fee Adjustment / Payment Reversal / Refund

## Decision
An installment schedule is a timing/allocation view of a Fee Head liability; it is not allowed to create or remove liability independently.

For every active installment-enabled Fee Demand Item:
- Sum of ACTIVE installment amounts must equal the current Fee Head net liability.
- No ACTIVE installment amount may be less than principal already paid against that installment.
- Proportional rebalancing is constrained by paid floors. If a row hits its paid floor, the remainder is redistributed across the other rows.
- New schedules store stable allocation percentages so future rebalancing uses the intended original distribution rather than recursively using already-adjusted amounts.
- Collection/Due Group projections must be capped by authoritative Fee Head open principal as a defensive safeguard against stale historical schedule data.

## Why
QA exposed a case where Fee Demand accounting was correct but cumulative CREDIT adjustments produced installment rows totaling ₹1,000 above the authoritative liability. That could mislead users and, without a defensive collection cap, could permit over-collection.

## Example
- Tuition gross: ₹20,000
- Installments: ₹10,000 + ₹10,000
- Paid on #1: ₹5,000
- Total CREDIT adjustments: ₹12,000
- Net Tuition liability: ₹8,000

Correct constrained result: ₹5,000 + ₹3,000. Outstanding Tuition = ₹3,000. The paid ₹5,000 is never rewritten or reduced.

## Non-goal
An adjustment does not refund posted money. If approved relief would need to reduce liability below principal already paid, use the controlled refund/payment-reversal workflow rather than forcing an installment below its paid floor.

## QA refinement — stable original allocation basis and due-date separation
Owner QA on 2026-09-14 exposed proportion drift after a CREDIT adjustment was later reversed. A ₹20,000 schedule originally created as ₹10,000 + ₹10,000 (50/50) correctly returned to ₹12,000 liability after reversal, but legacy rows with NULL `allocation_percentage` derived weights from already-adjusted amounts and produced ₹1,666.67 + ₹5,333.33 outstanding instead of the expected ₹1,000 + ₹6,000.

The authoritative rule is now:
- Every adjustment and every reversal recalculates from the ORIGINAL installment allocation basis, never from amounts produced by a previous adjustment.
- Existing `allocation_percentage` is authoritative for new schedules.
- For legacy schedules with NULL allocation percentages, the service recovers original schedule amounts from the schedule-creation audit; if unavailable, it uses the earliest adjustment BEFORE snapshot, then lazily persists the recovered percentages.
- Already-paid principal is an immutable floor.
- Due dates do not participate in allocation. They only classify the resulting unpaid amount as overdue/current/upcoming.
- Expiry of an installment due date does not make that installment disappear or transfer its liability into later installments.
- If adjusted total liability would fall below principal already paid, the adjustment is blocked and the refund/payment-reversal workflow must be used instead.

Example after reversal:
- Original: ₹10,000 + ₹10,000 (50/50)
- Paid on installment #1: ₹5,000
- Net liability after reversal: ₹12,000
- Recalculated effective amounts: ₹6,000 + ₹6,000
- Outstanding: ₹1,000 on #1 + ₹6,000 on #2 = ₹7,000
- If #1 due date has passed, the ₹1,000 is overdue; #2 remains upcoming. The amount split itself is unchanged by the date.
