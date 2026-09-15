# University Fixed Charges Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Purpose
Manage centrally controlled charges that affiliated Colleges can apply/collect but cannot modify.

## Route
/admin/university/finance/fixed-charges

## Permissions
- fee.structure.view
- fee.structure.create
- fee.structure.update
- fee.structure.approve

## Example Charges
- Registration / Enrollment
- Examination
- Migration
- Degree / Certificate

## Applicability
- Academic Session
- Degree / Program template
- Semester / Term where relevant
- Student category/admission type where policy permits

## Rules
- College cannot modify the controlled amount.
- Effective-date/version history required.
- Posted financial use prevents destructive overwrite.

## Realtime
REST only.
