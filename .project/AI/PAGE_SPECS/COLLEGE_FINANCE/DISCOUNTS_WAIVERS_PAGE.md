# Discounts & Waivers Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Purpose
Manage requests and approvals for permitted discounts, concessions and waivers.

## Route
/admin/colleges/:collegeId/finance/discounts-waivers

## Permissions
- fee.discount.request
- fee.discount.approve
- fee.waiver.request
- fee.waiver.approve

## Workflow
Request -> Review -> Approve/Reject -> Apply controlled adjustment

## Rules
- Reason required.
- Maker-checker when configured.
- Approval threshold follows University policy.
- Original fee/demand remains traceable.

## Realtime
REST; approval notifications may later use WebSocket.
