# College Finance Dashboard Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Purpose
Give authorized College finance users a clear overview of fee operations for their assigned College.

## Route
/admin/colleges/:collegeId/finance

## Permissions
- fee.view
- Scope: assigned College

## Summary
- Current demand
- Collected amount
- Outstanding
- Overdue
- Upcoming installments
- Recent collections
- Pending approvals

## Rules
Use real data only. No fake graphs. Cross-College access denied.

## Realtime
REST initially. WebSocket only later if meaningful live collection updates are required.
