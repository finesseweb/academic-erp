# Current Implementation State Patch — Applicant Email/Auth Isolation

Date: 2026-08-31

The Applicant Admission Portal email-verification feature is now isolated from normal ERP authentication. The shared User model no longer implements Laravel's global MustVerifyEmail contract. This prevents existing `verified` middleware on internal ERP routes from redirecting College Admin, University Admin, staff, or other internal users to the Email Verification page merely because their legacy/internal account has no `email_verified_at` value.

Applicant verification remains College-setting-driven and account-type-specific in ApplicantPortalController / PublicAdmissionApplicationController. The User model keeps Laravel's MustVerifyEmail trait only to provide verification helper methods used by that applicant-specific flow.
