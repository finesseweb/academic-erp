# ADR 045 — Curriculum Mapping Owns Source Discipline and Specialization Scope

Date: 2026-08-31
Status: Accepted

## Decision

`Course / Subject Master` remains a reusable academic master and does not decide the source discipline used for an applicant's curriculum choice presentation.

The authoritative context is `Curriculum Course Mapping`.

Each mapping may define:

- **Discipline** — the applicant/student discipline to which the mapped paper applies.
- **Applies to Specialization** — optional narrowing of that applicability. NULL means the paper applies to the whole selected Discipline.
- **Offered From Discipline** — optional source/owning discipline used to group cross-discipline choice/elective papers. NULL means Common / Interdisciplinary.
- **Course / Paper** — the reusable Course Master record.

## Example

BA English curriculum, Generic Elective category:

- Applicant Discipline: English
- Applies to Specialization: NULL
- Offered From Discipline: Political Science
- Course: Political Theory

The applicant sees the paper under `Generic Elective -> From Political Science`, while the paper remains applicable to English applicants.

For a specialization-specific paper:

- Applicant Discipline: English
- Applies to Specialization: English Literature
- Offered From Discipline: English
- Course: Victorian Literature

Only applicants selecting English Literature receive that mapping.

## Specialization Rule

Specialization on a curriculum course mapping is NOT a requirement that every applicant choose a specialization.

- NULL specialization = discipline-wide paper.
- Selected specialization = specialization-specific paper.
- Whether the applicant must choose a specialization is controlled separately by the Program Template Discipline `specialization_required` setting.

## Applicant / Student Inheritance

Mandatory curriculum mappings are inherited internally and do not require applicant selection.
Only CHOICE mappings are presented for applicant selection.
Choice grouping uses the mapping's `Offered From Discipline`, not Course Master.
Seat capacity remains outside application submission and is handled later in admission allocation.
