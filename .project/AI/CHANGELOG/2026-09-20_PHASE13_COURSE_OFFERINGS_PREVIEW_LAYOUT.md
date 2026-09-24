# Phase 13 — Course Offerings Preview Layout Correction

Date: 2026-09-20

## Change
- Widened the Add Course Offerings dialog on desktop while retaining viewport-safe responsive width.
- Increased the Curriculum Preview scroll area and added stable column widths for Course, Scope, Credits, Counting, and Rule.
- Added a minimum preview-table width so narrow screens scroll instead of crushing course names and metadata.

## Architecture impact
- UI-only correction.
- No schema, route, permission, service, curriculum, Batch, Student Enrollment, or Course Offering derivation changes.
- Owner QA remains required.

## Responsive modal overflow correction
- Corrected the DialogContent breakpoint width override (`sm:max-w-5xl`) so the shared Dialog default does not constrain the Course Offering modal to its small default width on desktop.
- Removed the forced 760px curriculum-preview table minimum width and horizontal scrolling.
- The preview now remains within the modal, wraps long cell content, and only uses vertical scrolling for long course lists.
