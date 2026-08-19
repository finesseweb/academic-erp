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
