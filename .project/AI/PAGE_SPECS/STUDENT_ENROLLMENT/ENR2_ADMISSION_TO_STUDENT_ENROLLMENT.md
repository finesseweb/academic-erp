# ENR-2 — Admission → Student Enrollment

**Status:** IMPLEMENTED / OWNER QA REQUIRED

ENR-2 extends the ENR-1 Eligibility Queue without replacing its filters or derived statuses.

## UI
READY rows expose **Enroll** only to users with `college_student_enrollment.enroll`. The action uses the shared theme-native confirmation dialog and semantic `UserPlus` icon. Confirmation displays Student, Admission, Programme, Session and current rendered Fee Clearance. Submission uses the shared loading infrastructure with `Enrolling student…`; server flash feedback uses the shared Toast system.

BLOCKED and ENROLLED rows expose no enrollment mutation.

## Server contract
`POST /college/{college}/student-enrollments/{admission}`

Before write, the server rechecks College scope, CONFIRMED Admission, Programme Offering, duplicate enrollment and authoritative Fee Clearance. Success creates/links Student and Student Enrollment in one transaction, promotes an existing Applicant account, copies governed Student-profile dynamic values and writes audit evidence.

## Not in ENR-2
Student UID / University Roll / Class Roll / Exam Roll generation (ENR-3); CSV migration/import (ENR-4); cancellation/re-enrollment policy unless separately approved.

## Corrective QA — Applicant identity path
- Staff-created application: enrollment may create Student with `user_id = NULL`; no Applicant Profile is created.
- Applicant-owned application: before mutation, `applicant_user_id` must resolve to an existing Applicant Profile in the same College.
- Successful applicant-owned enrollment must reuse the same user, set `applicant_profiles.student_id`, set lifecycle to `STUDENT_ENABLED`, and activate/promote the existing user to `STUDENT`.
- A missing, cross-College, or already-differently-linked Applicant Profile must fail atomically with no Student or Student Enrollment left behind.
- No migration is introduced by this corrective patch.

## ADR 202 — ENR2-11 mapping UI completion
The Application Form Builder exposes `student_data_policy` as **Data Usage** for University base fields and College-owned dynamic fields. Newly created dynamic fields default to Student Profile. Application Only remains available for admission-specific answers. Existing historical fields keep their stored policy, and Student Profile copies the submitted answer into `student_profile_values` only when a future enrollment creates the Student. Existing Student profiles are not retroactively rewritten by changing this setting.
