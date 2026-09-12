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

## Current Implemented Contract - 2026-09-12

- Actual route: `GET /college/{college}/fee-late-fines`.
- Late Fine calculation starts from the outstanding Demand Item due date or, when scheduled, the outstanding Installment due date.
- Those dates descend from the Academic Period validated Fee Setup and Demand snapshot, unless an installment explicitly supplies the later collection schedule.
- Charges retain their source Demand, Demand Item, optional Installment, source due date, calculation date, overdue days, base outstanding, and calculated fine.
- Recalculation/supersession does not alter the underlying Calendar Academic Period or Demand snapshot.
- Implemented status: `IMPLEMENTED / OWNER_QA_REQUIRED`; the earlier `NOT_STARTED` header is historical.
