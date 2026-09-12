# College Finance Reports Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Purpose
Provide finance reports for one affiliated College.

## Route
/admin/colleges/:collegeId/finance/reports

## Permissions
- fee.report.view
- fee.report.export

## Reports
- Demand vs collection
- Outstanding dues
- Installment collection
- Overdue installments
- Fee-head collection
- Program/semester collection
- Discounts / waivers
- Refunds / reversals
- Daily collection
- Receipt register

## Scope
Assigned College only unless University-level user has wider scope.

## Reporting Rule
Document data grain, joins and duplicate prevention before implementing each report.

## Realtime
REST/reporting queries.
