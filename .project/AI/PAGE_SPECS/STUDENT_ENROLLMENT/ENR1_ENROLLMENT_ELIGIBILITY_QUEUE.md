# ENR-1 — Enrollment Eligibility Queue

Status: IMPLEMENTED / OWNER QA REQUIRED

## Purpose
Provide a read-only, College-scoped queue of CONFIRMED Admissions that are ready, blocked, or already enrolled. ENR-1 must never create a Student or Student Enrollment.

## Route / permission
- GET `/college/{college}/student-enrollments`
- `college_student_enrollment.view`

## Filters
Academic Session -> dependent Programme Offering -> student/application/admission search -> Enrollment State -> rows per page. Filtering/pagination are server-side and use the shared application loading infrastructure.

## Eligibility contract
- Admission must be `CONFIRMED` and belong to the exact College.
- Programme Offering comes from the Admission's authoritative Intake -> Offering relationship.
- Financial eligibility MUST consume `FeeClearanceService::forAdmission()`; Enrollment code must not recalculate fee liability.
- `READY`: no active Enrollment for this Admission and Fee Clearance `is_cleared=true` (`CLEARED` or `NOT_REQUIRED`).
- `BLOCKED`: no active Enrollment and Fee Clearance gate is not open.
- `ENROLLED`: an existing `student_enrollments` record for the Admission has status `ENROLLED`.

## ENR-1 mutation boundary
No Enroll button, Student creation, Enrollment creation, UID/roll generation, CSV import or clearance override exists in ENR-1. Those belong to later approved milestones.

## UI contract
Use Project OS theme components, semantic Lucide icons, shared loading infrastructure, dependent filters, server pagination, status badges and responsive overflow handling. Empty results must show an explicit empty state.
