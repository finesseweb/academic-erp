# ADR 056 — Professional Applicant Progress, Discipline Resolution, and College Registration Numbers

Date: 2026-08-31
Status: Accepted

## Public Application Navigation
- Applicant Profile remains fixed in the right rail.
- The right-side Application Steps card is removed.
- Fixed header now contains a donut progress indicator plus arrow/chevron step navigation.
- The next step unlocks only after the current step passes required-field validation.
- Previously unlocked/completed steps remain editable until final submission.
- Locked future steps cannot be opened directly.

## Discipline Resolution
- Program Template is the applicant-facing authoring authority for main Discipline.
- If the active Curriculum contains explicit discipline-scoped mappings, only the valid intersection is offered.
- If Curriculum courses are program/common scoped, configured Program Template disciplines remain selectable instead of incorrectly returning an empty list.
- Active Curriculum, course, term, slot and specialization validity rules remain enforced.

## Registration Number
- College controls Registration Number format under Admission Form Setup.
- Supported tokens: `{COLLEGE_CODE}`, `{COLLEGE_ID}`, `{YEAR}`, `{YY}`, `{SEQ:n}`.
- Default: `{COLLEGE_CODE}/{YEAR}/{SEQ:6}`.
- Registration number is generated live from the saved College format.
- Sequence allocation is transactional (`lockForUpdate`) and not based on hard-coded User ID.
- Registration Numbers are finalized and persisted on Applicant Profile at the applicant's first successful application submission, using the College's current saved format and next transactionally reserved sequence. Once the applicant already has a submitted application in the College, the number is stable and later format edits do not rewrite it.
