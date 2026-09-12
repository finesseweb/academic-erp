# Current Implementation State Patch — Applicant/Student Identity

Stage 1 now uses authenticated Applicant accounts for mapped admission forms. The College mapping URL opens Registration/Login. Core identity is captured once and applications reference `applicant_user_id`. College-scoped settings control registration, email verification and CAPTCHA. The future Student lifecycle reuses the same `users` identity through `ApplicantStudentPromotionService`; Student master creation remains gated by final Admission Approval/Enrollment.
