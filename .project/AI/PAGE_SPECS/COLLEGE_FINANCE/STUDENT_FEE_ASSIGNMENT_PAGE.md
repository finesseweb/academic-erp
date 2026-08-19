# Student Fee Assignment Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Purpose
Assign the applicable approved fee structure and payment plan to eligible students.

## Route
/admin/colleges/:collegeId/finance/student-fee-assignment

## Permissions
- fee.structure.view
- fee.installment.assign

## Filters
- Academic Session
- Program
- Semester / Term
- Batch
- Category
- Student

## Actions
- Preview applicable fee
- Assign full-payment plan
- Assign approved installment plan
- Bulk assign to eligible students
- View assignment history

## Rules
Assignments derive from approved University + College fee components. Do not duplicate the source fee definition inside each student record unnecessarily.

## Realtime
REST only.
