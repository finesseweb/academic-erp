# ADR 207 — Test Data Cleanup Reset Levels

Status: IMPLEMENTED / OWNER QA REQUIRED
Date: 2026-09-18

## Decision
Test Data Cleanup has two explicit reset levels.

1. **Module Reset — Admission & Merit Workflow Reset**: resets generated Admission/Merit processing while preserving submitted Applications/applicant answers, Applicant identities, Admission Scores, Programme Offering, Intake/Seat Capacity, Curriculum and academic masters. It removes downstream generated Merit/Roster, Seat Allocation, Admission, admission-created canonical Student/Enrollment records and dependent admission finance test data. Reservation/Selection configuration downstream of Intake is reset so Intake can be deactivated, capacity changed, and the workflow regenerated.
2. **Full Academic Test Reset**: returns academic QA data to the documented clean starting state in dependency-safe order while preserving system/access bootstrap data. Users/login accounts are never deleted by this reset, including the designated `test@...` bootstrap login and the access records needed to log in.

## Canonical Student safety
Student cleanup must delete `student_enrollment_course_choices` before `student_enrollments`. Full reset covers both ADMISSION and IMPORT students; source type is not a cleanup branch.

## Permanent cleanup contract
Every new module that creates test/transactional records must extend the appropriate Module Reset/full-reset dependency graph and DB documentation in the same patch. Cleanup must keep foreign keys enabled, operate child-first, never guess dependencies, and preserve explicitly documented bootstrap/access data.


## Follow-up — Academic Calendar Period targeted cleanup (2026-09-18)

ADR 207 is extended with a targeted Academic Setup cleanup capability for Academic Calendar Period assignments. Test Data Cleanup may delete a selected `academic_calendar_term_periods` assignment without deleting the parent Academic Calendar. Calendar events are preserved; where an event has an optional reference to the deleted period assignment, that reference must be detached safely rather than deleting the event. This is a QA/reset operation only and does not alter curriculum terms, curricula, programme offerings, or fee validation rules.

The purpose is to allow an amended/current curriculum period to be re-assigned cleanly in the Academic Calendar during QA. Fee Management remains unchanged and continues to require valid Academic Calendar dates for the current curriculum period.
