# ADR 055 — Premium Fixed Application Shell and Free Step Navigation

Date: 2026-08-31
Status: Accepted

Public Regular Admission Application uses a fixed premium application shell:

- Header spans the complete application container and remains fixed while the form content scrolls.
- Main form content is the only desktop scrolling region.
- Applicant Profile is removed from the form-step sequence and shown permanently in a right-side profile rail.
- The profile rail includes a stable portal Registration Number (`REG-{college_id}-{user_id}`), name, DOB, email and phone.
- Application Steps are shown vertically in the right rail.
- Until final submission, applicants may jump freely to any step and return to edit previously entered data.
- Mobile keeps a compact horizontal step navigator inside the form content.
- Existing form, academic-selection, dynamic-field, review, validation and submit logic remains intact.
- No schema migration is required.
