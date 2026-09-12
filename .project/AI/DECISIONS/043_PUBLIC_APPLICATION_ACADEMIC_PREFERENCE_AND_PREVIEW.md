# ADR 043 — Public Application Academic Preference, Premium Journey and Seat-Decoupled Intake

Date: 2026-08-31
Status: Accepted

## Decision

The public Regular Admission application is an applicant-intake workflow, not a seat-allocation workflow.

1. A public applicant may submit while the mapped Admission Cycle is open regardless of current intake/seat capacity.
2. Seat capacity, reservation buckets, merit/roster rules and seat allocation remain downstream Admission processing. They must not block or limit public form submission.
3. The mapped Program Offering remains the application context. At the beginning of the application, the applicant selects an allowed Discipline and, when configured, an allowed Specialization from that Program Template.
4. Academic selections must come from the exact Curriculum linked to the mapped Program Offering.
5. ACTIVE curriculum slots with `selection_mode = MANDATORY` are auto-allotted for the chosen Discipline/Specialization context.
6. ACTIVE curriculum slots with `selection_mode = CHOICE` expose only their applicable mapped Course/Paper options and enforce the Curriculum Slot minimum/maximum selection rules.
7. Applicant academic preference and selected/auto-allotted courses are persisted separately from Admission Seat Bucket choices so later Student Enrollment can reuse them without treating them as seat allocations.
8. Public application UI must remain outside the internal ERP shell, include an explicit Logout action, respect the configured template step mode, and provide a final Review & Submit preview.

## Data boundary

New applicant-stage persistence:
- `college_admission_application_academic_preferences`
- `college_admission_application_course_choices`

These tables capture applicant academic intent. They do not reserve a seat and do not decrement capacity.

## Lifecycle

Applicant Registration/Login → mapped Program Offering → Discipline → optional Specialization → mandatory courses auto-allotted + choice courses selected → configured application steps → preview → submit → later Eligibility/Interview/Merit/Roster/Seat Allocation → Admission Approval/Enrollment → Student.
