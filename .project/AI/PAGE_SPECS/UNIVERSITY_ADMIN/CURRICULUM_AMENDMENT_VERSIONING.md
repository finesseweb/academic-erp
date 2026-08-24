# Curriculum Amendment / Versioning

## Status
Implementation: IMPLEMENTED_IN_REPLACEMENT_PACKAGE
Repository validation: REQUIRED_AFTER_COPY

## Purpose
Permit controlled changes after Curriculum approval without changing historical approved academic data.

## Entry
Curriculum list -> `More` -> `Amend Curriculum`

The action is exposed only when the selected Curriculum is the current `ACTIVE / APPROVED` version and no unresolved amendment already exists from it.

## Route
`POST /admin/curricula/{curriculum}/amend`

Route name:
`curricula.amend`

## Permission
Uses existing `curriculum.update` permission.

No new permission code is introduced in this implementation.

## Amendment Form
Required:
- New Curriculum Code
- New Version
- Amendment Type
- Reason for Amendment

Optional:
- Effective From

Supported Amendment Type values:
- `CORRECTION`
- `COURSE_REPLACEMENT`
- `TERM_SEMESTER_CHANGE`
- `SLOT_CHANGE`
- `CREDIT_CHANGE`
- `STRUCTURE_CHANGE`
- `OTHER`

## Creation Rule
The source must be:
- lifecycle `ACTIVE`
- approval `APPROVED`
- the current approved version
- without an existing non-retired direct amendment

The new version is created as:
- same `university_id`
- same `program_template_id`
- same `academic_session_id`
- `parent_curriculum_id = source.id`
- lifecycle `DRAFT`
- approval `NOT_SUBMITTED`
- validation checkpoint cleared

The complete Terms -> Slots -> Course Mappings structure is copied as independent rows.

## Editing Rule
After the DRAFT amendment is created, existing DRAFT Curriculum rules apply. The user may change:
- Curriculum header fields allowed by the normal DRAFT editor
- Term/Semester name and sequence
- Term status
- Slot name
- Slot Category and Type
- Slot Credits
- Mandatory/Choice selection rules
- Slot display order
- Course/Paper Mapping
- Discipline/Specialization context
- Course Mapping order/status
- other currently supported DRAFT structure values

Program Template and Academic Session are not chosen in the amendment dialog because an amendment belongs to the same Curriculum family/context.

## Approval Rule
The amendment uses the existing flow:

`DRAFT -> Validate Structure PASS -> Submit for Approval -> configured stages -> Final Approval -> ACTIVE / APPROVED`

Until final approval, the old approved version remains Current.

After final approval, Current/Previous is derived:
- newly approved amendment = Current
- its approved parent = Previous

The previous record remains available for historical student/exam/result/audit references.

## UI Rule
Curriculum Version cell shows:
- `Current` for the latest approved active version
- `Previous` for an approved active version replaced by an approved amendment
- `Amendment of vX` for linked amendment rows

Previous approved versions do not expose Retire from the Curriculum list because they are retained as version history.

## Audit
New event:
`CURRICULUM_AMENDMENT_CREATED`

The event records source/target IDs and versions plus amendment metadata.
