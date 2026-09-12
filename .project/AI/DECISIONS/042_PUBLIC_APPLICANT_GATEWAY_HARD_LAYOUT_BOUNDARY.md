# ADR 042 — Public Applicant Gateway Hard Layout Boundary

## Decision
The public admission entry route `/apply/{slug}` renders a dedicated page component: `public/admission-gateway`.

The page is explicitly layout-less at page level and is also excluded from the global internal ERP layout resolver. This creates a hard boundary so public applicants can never inherit the College/University dashboard sidebar or internal navigation.

## Reason
Applicant registration/login is an external admission experience, not an internal ERP workspace. A previous implementation relied only on a global page-name layout exception and could still surface stale/internal layout behavior. A dedicated page name plus page-level `layout = page => page` prevents that regression.

## UI rule
The portal remains theme-aware by using existing theme tokens and shared UI components. The shared ERP DatePicker must be reused for applicant DOB and public template DATE fields.
