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
