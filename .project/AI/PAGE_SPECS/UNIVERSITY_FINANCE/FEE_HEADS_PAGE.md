# University Fee Heads Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Purpose
Create and maintain the canonical fee heads/classifications used by University and affiliated Colleges.

## Route
/admin/university/finance/fee-heads

## Permissions
- fee.head.view
- fee.head.create
- fee.head.update

## Fields
- Name
- Code
- Description
- Control type: UNIVERSITY_FIXED / COLLEGE_CONFIGURABLE / COLLEGE_DEFINED_ALLOWED
- Accounting category
- Active status

## Rules
- Code unique.
- A head referenced by posted financial activity is not hard-deleted.
- Control type changes require impact review and audit.

## UI
Search, filter by control type/status, create/edit drawer or page, status badge, audit-safe actions.

## Realtime
REST only.
