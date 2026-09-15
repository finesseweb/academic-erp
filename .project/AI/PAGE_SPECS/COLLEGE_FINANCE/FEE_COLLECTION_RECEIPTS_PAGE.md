# Fee Collection & Receipts Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Purpose
Record authorized fee payments and issue traceable receipts.

## Route
/admin/colleges/:collegeId/finance/collections

## Permissions
- fee.collect
- fee.receipt.print

## Workflow
1. Find student/demand.
2. Show current due and installment status.
3. Enter allowed payment.
4. Validate payment allocation.
5. Commit transaction.
6. Generate stable receipt.

## Rules
- Prevent duplicate submission.
- Payments are never hard-deleted after posting.
- Reversal/refund uses explicit workflow.
- Receipt has stable unique reference.
- Actor and timestamp are audited.

## Realtime
REST for collection. Optional notification event later if justified.

## Current Implemented Contract - 2026-09-12

- Actual route: `GET /college/{college}/fee-payments`.
- Open Payables group Demand Items and Installments by effective due date and show due-now and next-due values.
- Approved Benefits reduce available payable amounts before collection; Late Fine charges join the same collection candidate set.
- Deterministic allocation order is Mandatory Principal, Mandatory Late Fine, Optional Principal, then Optional Late Fine; oldest due date orders entries inside a category.
- `fee_payment_allocations` retain Demand, Demand Item, optional Installment/Late Fine, source type, due date, mandatory flag, and sequence.
- Offline posting and verified TEST online checkout both use `FeePaymentService`; the Calendar Academic Period is not re-resolved at payment time.
- Implemented status: `IMPLEMENTED / OWNER_QA_REQUIRED`; refunds/reversals remain separate future workflow.

## Adjustment-aware installment availability — QA hardened 2026-09-14
Due Groups are a collection projection, not an independent liability source. For installment-enabled Fee Heads, the total principal exposed for collection must never exceed the authoritative Fee Head open principal after benefits, POSTED adjustments, POSTED payments, reversals, and refunds. A stale/historical installment schedule must therefore be defensively capped rather than allowing collection above the Fee Demand balance.

## Installment due-state rule
Fee Collection displays the recalculated installment outstanding from the stable original schedule basis. Due dates classify amounts as overdue/current/upcoming only. They must not alter installment allocation proportions or silently transfer an expired installment's remaining principal into a later installment.
