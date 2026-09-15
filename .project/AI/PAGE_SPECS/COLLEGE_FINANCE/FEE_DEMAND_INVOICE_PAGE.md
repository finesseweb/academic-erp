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

## Current Implemented Contract - 2026-09-12

- Actual route: `GET /college/{college}/fee-demands`.
- Demand generation supports bulk and individual execution through the same applicability service.
- The selected academic context is resolved against effective Fee Setup and current academic eligibility.
- `fee_demands` snapshots billing period number/label/basis and academic/program/curriculum context.
- `fee_demand_items` snapshot source period number, effective due date, amount, Fee Head, mandatory status, enrollment-clearance status, installment permission, and refundability.
- Existing Demands are not reinterpreted when Fee Setup or Calendar Academic Period dates later change.
- Implemented status: `IMPLEMENTED / OWNER_QA_REQUIRED`; the earlier `NOT_STARTED` header is historical.
