# ADR 038 — Applicant Portal Email Verification, Recovery and Theme UX

Date: 2026-08-31

## Decision

1. Applicant Portal registration/login remains a public admission gateway scoped by the mapped College admission URL.
2. Applicant Forgot Password reuses the ERP/Laravel secure password reset flow; no second password store or applicant-only credentials are introduced.
3. When College Applicant Registration requires email verification, registration sends a real signed verification email. The applicant cannot access the application until the email is verified. Resend is throttled.
4. The signed applicant verification link returns the verified applicant to the same mapped admission application rather than the internal staff dashboard.
5. Public applicant pages must use the same theme tokens/components as the ERP (`primary`, `background`, `card`, `muted`, etc.), so College/theme changes propagate instead of using hard-coded brand colours.
6. Applicant Registration Date of Birth and all dynamic DATE fields in the public admission form use the shared ERP `DatePicker` component, matching the internal admission application UI.
7. Decorative portal artwork must remain theme-derived and non-functional; it must not alter admission hierarchy, RBAC, field definitions, conditional logic or template mapping.

## Consequence

The Applicant Portal can be visually richer without becoming a separate design system, and date entry behaves consistently between internal College forms and the applicant-facing form.
