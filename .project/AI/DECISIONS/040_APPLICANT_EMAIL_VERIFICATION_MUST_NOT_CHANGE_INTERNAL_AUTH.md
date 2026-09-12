# ADR 040 — Applicant Email Verification Must Not Change Internal ERP Authentication

## Status
Accepted — 2026-08-31

## Context
Applicant email verification was introduced for the public Admission Form applicant registration flow. A previous patch made the shared `User` model implement Laravel's global `MustVerifyEmail` contract. Existing internal ERP routes already use Laravel's `verified` middleware. This unintentionally redirected College Admin, University Admin and other internal users whose `email_verified_at` was null to the generic Email Verification screen.

That behavior conflicts with the frozen ERP authentication/RBAC hierarchy. Applicant registration settings are College-scoped Admission Form settings and must not redefine authentication requirements for internal ERP accounts.

## Decision
- The shared `User` model MUST NOT globally implement `Illuminate\\Contracts\\Auth\\MustVerifyEmail` solely because Applicant Registration can require verification.
- The `Illuminate\\Auth\\MustVerifyEmail` trait may remain on `User` only as a reusable implementation helper for applicant verification methods (`hasVerifiedEmail`, `markEmailAsVerified`, `getEmailForVerification`).
- Applicant email verification is enforced only by the Applicant Admission Portal controllers and the College Applicant Registration setting.
- `email_verification_required = true` affects only `account_type = APPLICANT` in the matching College public admission flow.
- College Admin, University Admin, Super Admin, staff and other internal ERP users continue through the pre-existing internal authentication/RBAC behavior and must not be redirected because Applicant verification is enabled.
- Forgot Password exposed on the Applicant Login page is an Applicant Portal capability and must not be used as a reason to modify internal role/login policy.

## Runtime Rule
Applicant registration -> optional applicant email verification -> applicant application.

Internal ERP login -> existing active-user + RBAC flow, unchanged.

## Regression Guard
Do not add the global `MustVerifyEmail` contract back to the shared User model unless a separately approved ERP-wide authentication decision explicitly requires email verification for every internal account type.
