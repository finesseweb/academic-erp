# ADR 041 — Applicant Portal Public Shell and Theme Boundary

## Decision
Applicant-facing admission pages under `applicant/*` are public admission experiences and must not inherit the internal ERP `AppLayout` (sidebar, Dashboard navigation, internal header).

The applicant gateway must remain theme-aware by using the existing design tokens (`primary`, `background`, `card`, `muted`, `accent`, etc.) rather than fixed brand colors. It may show mapped admission context (College, University, Program Offering, Admission Cycle/Session) so the applicant knows what they are applying for.

The shared ERP `DatePicker` remains the authoritative date control for Applicant Registration DOB and dynamic DATE fields.

## Boundaries
- Internal College/University/Admin screens continue to use the existing ERP layout and RBAC.
- Applicant portal does not introduce a parallel theme system.
- Applicant portal does not expose internal Dashboard navigation.
- Applicant Registration Enabled/Disabled behavior is unchanged.
- Email verification, CAPTCHA, applicant identity and admission mapping rules are unchanged.
