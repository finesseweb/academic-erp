# Dues & Late Fees Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Purpose
Track outstanding and overdue student balances and apply approved late-fee rules.

## Route
/admin/colleges/:collegeId/finance/dues

## Permissions
- fee.view
- fee.report.view

## Filters
- Session
- Program
- Semester
- Due Status
- Due Date
- Student

## Information
- Student
- Demand/installment
- Due date
- Original due
- Late fee
- Paid
- Outstanding
- Days overdue

## Rules
Late fee comes from approved policy; manual overrides require explicit permission/audit.

## Realtime
REST only.
