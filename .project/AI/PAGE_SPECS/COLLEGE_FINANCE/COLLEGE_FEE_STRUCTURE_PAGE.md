# College Fee Structure Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Purpose
Allow an affiliated College to configure fee structures permitted by University policy.

## Route
/admin/colleges/:collegeId/finance/fee-structures

## Permissions
- fee.structure.view
- fee.structure.create
- fee.structure.update
- fee.structure.approve

## Scope
College

## Structure Dimensions
- Academic Session
- Program
- Semester / Term
- Student Category
- Quota / Admission Type
- Optional Service where applicable

## Fee Components
- UNIVERSITY_FIXED: displayed read-only
- COLLEGE_CONFIGURABLE: editable only within University rule
- COLLEGE_DEFINED: addable only when University policy permits

## Actions
- Create Draft
- Edit Draft
- Submit for Approval
- Approve / Reject
- Duplicate for new session
- Activate / Deactivate
- View version history

## Integrity
Posted use prevents destructive overwrite. New approved versions preserve history.

## Realtime
REST only.

## Current Implemented Contract - 2026-09-12

- Actual route: `GET /college/{college}/fee-management` (with University setup at `GET /admin/fee-management`).
- Fee Structures support `ONE_TIME`, `PER_TERM`, `PER_ACADEMIC_YEAR`, `SPECIFIC_TERM`, and `SPECIFIC_ACADEMIC_YEAR` collection bases.
- Period choices derive from ACTIVE Terms in the exact current approved Curriculum; College setup uses the Curriculum on the Program Offering.
- Recurring Fee Items store period-specific amount, applicability, mandatory/enrollment-clearance/installment flags, ordering, status, and Standard Due Date.
- A recurring Standard Due Date must fall inside the matching University Calendar Academic Period. Academic-year due dates require all covered Term periods to exist.
- The Calendar Period is a validation boundary; Fee Setup stores `period_no` and due date rather than a mutable Calendar Period foreign key.
- Implemented status: `IMPLEMENTED / OWNER_QA_REQUIRED`; the earlier `NOT_STARTED` header is historical.
