# ADR 048 — Admission Academic Package Selection Without Semester UI

Date: 2026-08-31
Status: Accepted

## Decision

Admission application capture is not semester course registration. Public Applicant and internal College Admin application entry must not force the user to browse Semester 1, Semester 2, etc.

Curriculum terms and slots remain authoritative internally, but applicant-facing academic choices are presented by Course Category and Offered From Discipline.

### Academic package mode

When one Offered From Discipline can completely satisfy every CHOICE slot of a Course Category with the exact configured selection count, it is treated as an academic package.

Example: MINOR has one fixed History paper in each applicable term. The applicant sees only `History`. Selecting History links all underlying History curriculum mappings across the relevant terms.

### Course-level choice mode

If an Offered From Discipline contains a genuine choice (for example three History papers are offered but the slot requires one), the individual course/paper options remain visible.

### Validation

The backend reconstructs the same package rule from the approved curriculum and rejects mixed/incomplete package selections. This prevents a manipulated request from selecting History in one term and Sociology in another when the curriculum defines them as coherent academic packages.

### Persistence

No new persistence table is introduced. Underlying selected curriculum mappings continue to be stored in `college_admission_application_course_choices`; mandatory curriculum remains `AUTO_MANDATORY`, while package/course selections remain `APPLICANT_CHOICE`.

### Downstream

Semester placement remains in the Curriculum and later Student course-allotment lifecycle. Admission form UI only captures the applicant's academic choice. Seat capacity/reservation remains downstream and does not block application submission.
