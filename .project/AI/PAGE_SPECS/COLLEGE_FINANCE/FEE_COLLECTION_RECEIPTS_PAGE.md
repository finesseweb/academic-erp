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
