# Installment Rescheduling Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Purpose
Reschedule an individual student's installment schedule when policy permits, without destroying the prior schedule.

## Route
/admin/colleges/:collegeId/finance/installment-rescheduling

## Permissions
- fee.reschedule.request
- fee.reschedule.approve

## Workflow
Select student -> show current schedule -> propose revised dates/amount distribution -> reason -> submit -> approve/reject -> activate new version.

## Rules
- Preserve original schedule.
- Paid installments remain historically intact.
- Revised schedule must reconcile remaining payable amount.
- Cannot alter UNIVERSITY_FIXED charge itself.
- Audit requester, approver, reason and before/after schedule.

## Realtime
REST only.
