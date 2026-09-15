# Refunds & Reversals Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Purpose
Handle corrections to posted financial activity without deleting history.

## Route
/admin/colleges/:collegeId/finance/refunds-reversals

## Permissions
- fee.refund.request
- fee.refund.approve

## Actions
- Request refund
- Request payment reversal
- Approve / Reject
- View history

## Rules
- Never delete original payment.
- Link reversal/refund to original transaction.
- Reason mandatory.
- Approval and audit required.
- Updated balance is derived from original + approved adjustments.

## Realtime
REST; notifications optional later.
