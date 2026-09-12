# ADR 034 — Applicant Identity → Student Portal Lifecycle

## Decision
The mapped admission URL is an **Applicant Registration/Login entry point**, not an anonymous full application form.

- Candidate registers once with Name, DOB, Email, Mobile and Password.
- These values are stored in `users` + `applicant_profiles` and reused by every application; the dynamic form does not ask for the same core identity again.
- College controls `registration_enabled`, `email_verification_required`, and `captcha_required` through RBAC permission `college_applicant_registration.settings`.
- After login, Program Offering + Admission Cycle mapping resolves the same University Base / College Extension form and all existing conditional/scoped field rules remain authoritative.
- Submitted applications remain in the existing Application/Application Choice chain and carry `applicant_user_id`.
- An Applicant is **not** a Student merely because an application was submitted or selected.
- Final Admission Approval/Enrollment creates the real Student record, then `ApplicantStudentPromotionService::enableStudentAccess()` links that Student ID and changes the same login from APPLICANT to STUDENT.
- Rejected/not-admitted applicants remain Applicant accounts. No duplicate Student login is created.

## Security
Registration settings are College-scoped and server-enforced. CAPTCHA is checked server-side when enabled. Email verification gates application access when required. Student promotion is a transactional service contract for the later Admission Approval/Enrollment module.
