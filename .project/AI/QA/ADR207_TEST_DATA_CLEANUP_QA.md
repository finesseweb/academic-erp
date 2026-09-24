# ADR 207 — Owner QA

## Admission & Merit Module Reset
1. Keep at least one submitted Application and generated Merit/Roster with downstream Admission/Student test data.
2. Run Test Data Cleanup → Admission & Merit Workflow Reset using `RESET-ADMISSION-WORKFLOW-TEST-DATA`.
3. Confirm submitted Applications and applicant answers remain.
4. Confirm Merit entries, Seat Allocations, Admissions and admission-created Student/Enrollment/Course Choices are removed.
5. Confirm Programme Offering, Intake/Seat Capacity, Curriculum and Admission Scores remain.
6. Deactivate Intake, change seat capacity, rebuild Reservation/Selection setup, regenerate Merit/Roster.
7. Confirm preserved/new eligible Applications participate according to normal Merit rules.

## Full Academic Test Reset
1. Preview counts includes Student, Enrollment, Enrollment Course Choice and Student Profile data.
2. Run guarded Full Academic Test Reset.
3. Confirm ADMISSION and IMPORT Students are removed child-first without FK errors.
4. Confirm the current/designated `test@...` bootstrap login still works immediately after reset.
5. Confirm Users/Roles/Permissions/Audit Logs and documented system/access core are preserved.

Status: OWNER QA PENDING.


## Follow-up QA — Academic Calendar Period targeted reset

1. Open Test Data Cleanup → Academic Setup and confirm Academic Calendar Periods are listed as individually selectable cleanup targets.
2. Preview/select one known calendar-period assignment and confirm the parent calendar, curriculum, curriculum term, programme offering, and fee configuration are not included for deletion.
3. Execute cleanup and confirm only the selected `academic_calendar_term_periods` assignment is removed.
4. Confirm Academic Calendar still opens and existing calendar events remain present.
5. Confirm the removed curriculum period can be added again through Add Academic Period.
6. Confirm Fee Item validation still blocks saving until the current curriculum period has valid Academic Calendar dates.
7. Regression: Module Reset and Full Academic Test Reset remain available; protected bootstrap/login identity behavior from ADR 207 remains unchanged.

Status: implementation prepared; owner QA pending.
