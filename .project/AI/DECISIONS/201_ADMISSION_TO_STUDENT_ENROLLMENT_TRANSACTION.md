# ADR 201 — Admission → Student Enrollment Transaction

**Status:** IMPLEMENTED / OWNER QA REQUIRED  
**Date:** 2026-09-16  
**Milestone:** ENR-2

## Decision
ENR-2 is the single authoritative transaction that converts a College-scoped CONFIRMED Admission into the Student lifecycle. It revalidates the Admission, Programme Offering and live Fee Clearance on the server inside the enrollment transaction. A READY label from a previously rendered page is never sufficient authorization.

The transaction creates or reuses the Admission-linked `students` row, creates exactly one `student_enrollments` row for that Admission, preserves `source_type=ADMISSION`, snapshots only dynamic application fields explicitly governed as `STUDENT_PROFILE`, promotes the existing Applicant login through `ApplicantStudentPromotionService` when one exists, and records a College-scoped audit event.

ENR-2 deliberately does not generate `student_uid`, University Roll, Class Roll or Exam Roll. Identifier ownership remains ENR-3.

## Invariants
- Admission must still be `CONFIRMED` and belong to the route College.
- Programme Offering must resolve from the confirmed Admission and belong to that College.
- `FeeClearanceService` is re-run immediately before mutation; pending required liability blocks enrollment.
- Existing active enrollment is idempotent at service level; duplicate enrollment rows are additionally protected by database uniqueness.
- Applicant identity is promoted; a second user/login is never created.
- Dynamic application fields never become Student columns automatically. Only `STUDENT_PROFILE` fields with a profile key are copied to `student_profile_values`.
- Enrollment is auditable and permission-protected by `college_student_enrollment.enroll`.

### Owner-QA corrective clarification — Applicant identity promotion
ENR-2 supports two legitimate Admission origins. For an applicant-owned application (`applicant_user_id` present), enrollment must preflight an existing same-College `applicant_profiles` row, reuse that existing `users` identity, link `applicant_profiles.student_id`, and promote the same login to `STUDENT`. Missing/cross-College/conflicting Applicant Profile identity blocks and rolls back enrollment. For a staff-created application (`applicant_user_id` absent), `students.user_id = NULL` is valid and ENR-2 must not manufacture an Applicant Profile. This hardening introduces no database migration or new domain table.
