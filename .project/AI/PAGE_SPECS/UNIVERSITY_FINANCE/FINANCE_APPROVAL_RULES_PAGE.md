# Finance Approval Rules Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Purpose
Configure maker-checker and approval requirements for sensitive finance operations.

## Route
/admin/university/finance/approval-rules

## Permissions
- fee.policy.view
- fee.policy.update

## Configurable Events
- Fee structure approval
- Installment plan approval
- Discount approval
- Waiver approval
- Refund approval
- Rescheduling approval
- Large manual adjustment approval

## Rules
Approver must have required permission and valid scope. Where maker-checker is enabled, maker cannot approve own request.

## Realtime
REST for changes; notification delivery may later use WebSocket.
