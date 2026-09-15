# University Finance Reports Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Purpose
Provide consolidated University-level financial reporting across all affiliated Colleges.

## Route
/admin/university/finance/reports

## Permissions
- fee.report.view
- fee.report.export

## Filters
- College
- Academic Session
- Program
- Fee Head
- Date Range
- Payment Status

## Reports
- Total demand
- Total collected
- Outstanding dues
- Overdue installments
- College-wise collection
- Fee-head-wise collection
- Waivers/discounts/refunds
- Reconciliation summaries

## Rules
Report grain and joins must be documented before implementation to avoid duplicated financial totals.

## Realtime
REST/report queries by default.
