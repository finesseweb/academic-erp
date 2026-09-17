
## Student link finalization — ENR-0 / ADR 199 — 2026-09-16
`student_id` is now an explicit FK to `students.id` with RESTRICT delete behavior. It remains null while the identity is only an Applicant. ENR-2 populates it only after the Student master exists and then uses `ApplicantStudentPromotionService` to enable the same User as STUDENT.
