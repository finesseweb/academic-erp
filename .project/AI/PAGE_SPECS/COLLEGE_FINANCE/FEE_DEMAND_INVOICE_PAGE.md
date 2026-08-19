# Fee Demand / Invoice Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Purpose
Generate and view stable student financial demands/invoices from approved fee assignments.

## Route
/admin/colleges/:collegeId/finance/demands

## Permissions
- fee.view
- fee.collect

## Information
- Student
- Fee assignment
- Demand reference
- Fee components
- Installment/due mapping
- Total
- Paid
- Outstanding
- Status

## Rules
- Demand references are stable.
- Posted demands are not deleted merely because a later correction occurs.
- Corrections use adjustment/reversal history.

## Realtime
REST only.
