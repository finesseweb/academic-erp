# College Scholarship Assignment Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE

## Purpose
Assign approved scholarship schemes to eligible students in the College.

## Route
/admin/colleges/:collegeId/finance/scholarship-assignments

## Rules
Eligibility validation, approval, benefit calculation and audit required.
Original fee/demand remains traceable.
Implementation requires explicit owner approval.

## Current Implemented Student Benefit Contract - 2026-09-12

- Actual route: `GET /college/{college}/fee-student-benefits`.
- Benefits are assigned against an existing Fee Demand and its eligible Demand Items, after academic-period-specific Fee Setup has been snapshotted into the Demand.
- Benefit rows retain the Demand, Admission, Scheme, eligibility, and calculation snapshots; item rows retain the affected Demand Item and Fee Head.
- Approval adjusts the Demand payable balance and reconciles installment amounts where applicable. It does not modify the Calendar Academic Period, billing period identity, original Fee Setup, or original Demand liability.
- Bulk selection may filter/group by academic context and billing-period label, but the Demand remains the financial authority.
- Implemented status: `IMPLEMENTED / OWNER_QA_REQUIRED`; the earlier `NOT_STARTED` header is historical.
