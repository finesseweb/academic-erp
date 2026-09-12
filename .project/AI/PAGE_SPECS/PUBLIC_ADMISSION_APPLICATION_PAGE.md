# Public Admission Application Page

## Purpose
Applicant-facing admission form for completing academic selection, configured dynamic application steps, review, and final submission.

## Step-by-step layout
- The application header contains the programme/cycle context and the step navigation.
- Applicant Help is displayed in the main application workspace immediately below the header/step divider and above the active step content.
- Help uses the College-configured Phone, Email, and Help Notes/Instructions values. Phone and email remain actionable links. When no custom help note is configured, a neutral admission-office fallback instruction is displayed.
- The Applicant Profile remains in the desktop right sidebar.
- The Application Progress card remains in the desktop right sidebar below Applicant Profile. It continues to show percentage complete, current step, and the horizontal progress bar using the existing unlock/progress calculation.
- Help is not duplicated in the desktop sidebar.
- Responsive layouts keep Help in the main workspace so it remains visible when the desktop sidebar is hidden.

## Styling constraints
- Reuse existing project UI components and theme utility classes.
- No external stylesheet or frontend dependency is introduced for this layout.

## Data / behavior
- This is a presentation/layout change only.
- Admission validation, step unlocking, final submission, registration-number behavior, and backend authorization remain unchanged.
- No database migration is required.

### Dynamic document/file fields
- FILE and IMAGE controls must synchronize their selected `File` into the same client-side field-state map used by step validation and Review & Submit.
- A selected required file counts as a completed field for step progression; Laravel remains authoritative for file size/type validation during submission.
- The UI shows the selected original file name and size, and uses configured allowed extensions for the browser `accept` filter where available.
- Uploaded admission documents remain private and are not exposed through public storage URLs.

## Duplicate application rule (2026-09-03)
- One applicant may submit applications to multiple different Program Offerings.
- The same applicant may not hold more than one active (`DRAFT` or `SUBMITTED`) application for the same College Admission Cycle. Since a College Admission Cycle belongs to one Program Offering, this is the enforcement key for `Applicant + Program Offering + Admission Cycle`.
- When an active application already exists, the public form is non-submittable and exposes `ALREADY_APPLIED` with the existing Application Number.
- The backend repeats this check transactionally and locks the applicant identity during creation; UI checks are not the security/integrity boundary.
- A `WITHDRAWN` application does not block a later re-application under the current policy.
