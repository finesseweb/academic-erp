# Course Delivery — Course Offerings

Status: IMPLEMENTED — OWNER QA PASSED / CLOSED (2026-09-21)
Date: 2026-09-20
Decision: ADR 209

## Purpose
Create the operational delivery instance of an existing Curriculum course for an existing College Batch. Course Offering is not a Course Master, Program Offering replacement, Curriculum editor, Batch replacement, or Section replacement.

## Route
- GET `/college/{college}/course-offerings`
- POST `/college/{college}/course-offerings`
- PATCH `/college/{college}/course-offerings/{courseOffering}/status`

## Existing hierarchy preserved
`University -> College -> Program Offering -> Batch -> Section`

Curriculum authority remains:
`Program -> Curriculum -> Curriculum Term -> Curriculum Slot -> Curriculum Course Mapping -> Course`

Course Delivery connects those existing structures as:
`Batch + Curriculum Course Mapping -> Course Offering`

The selected Curriculum Course Mapping MUST belong to the exact Curriculum already linked by the Batch's parent Program Offering. No Program, Session, Curriculum, Batch or Section ownership is duplicated onto `course_offerings`.

## Why Section is not stored here
Course Offering is batch-level delivery availability. Sections remain existing Batch children and are intentionally not redefined. Section-specific teaching allocation/timetable/class scheduling belongs to later Phase 13 milestones. This avoids creating duplicate Course Offerings solely because a Batch has multiple Sections.

## Create rules
- College must be ACTIVE.
- Batch must belong to the route College and be ACTIVE under an ACTIVE Program Offering.
- Creation is bulk and Curriculum-driven: `Batch -> University-defined Discipline -> Term -> derived applicable courses`.
- Discipline options come from existing Curriculum Course Mappings; Batch names/codes are never parsed to infer discipline.
- For the selected Term, MANDATORY slots include applicable common + selected-discipline mappings.
- All active applicable CHOICE mappings are shown even when no Student Enrollment exists yet; the College selects which Choice courses it plans to run.
- Preview shows Course, scope, Slot, credits, credit-counting and delivery rule before creation. MANDATORY rows are auto-selected/locked; CHOICE rows are selectable.
- The Add Course Offerings dialog uses a wide responsive desktop layout so the Curriculum Preview columns remain readable; on smaller viewports it stays within the viewport and the preview table may scroll horizontally.
- Credits and `credit_counting` are read-only inherited Curriculum values; Course Offering cannot edit them.
- Existing `Batch + Curriculum Course Mapping` offerings are skipped, making repeat bulk creation safe.
- New Course Offerings start `INACTIVE`.
- Notes are optional and apply to newly-created records.

## Activation rules
Activation requires:
1. College ACTIVE.
2. Batch ACTIVE.
3. Batch parent Program Offering ACTIVE.
4. Curriculum Term ACTIVE.
5. Curriculum Slot ACTIVE.
6. Curriculum Course Mapping ACTIVE.

Backend validation is authoritative.

## RBAC
- `college_course_offering.view`
- `college_course_offering.create`
- `college_course_offering.enable` — sensitive
- `college_course_offering.disable` — sensitive

All are College-delegable and server-enforced with exact College ownership inherited through Batch -> Program Offering -> College. Default grants: `SUPER_ADMIN`, `COLLEGE_ADMIN`.

## Audit
- `COURSE_OFFERING_CREATED`
- `COURSE_OFFERING_ACTIVATED`
- `COURSE_OFFERING_DEACTIVATED`

## Phase boundary
This milestone does NOT implement Faculty Allocation, Timetable, Rooms, Class Scheduling, Attendance, or Section-specific class delivery. Those remain later hierarchy milestones.

## QA
1. Select a College Batch and verify Discipline options are only University-defined disciplines present in its linked Curriculum.
2. Select Discipline + Term and verify preview contains MANDATORY common courses plus MANDATORY selected-discipline courses.
3. Verify all active applicable CHOICE courses appear even with zero/no Student Enrollments; verify each Choice course can be selected/deselected for delivery.
4. Verify preview shows Curriculum credits and COUNTABLE/NON_COUNTABLE (or configured value) read-only.
5. Create bulk offerings and verify all MANDATORY plus selected CHOICE courses are created INACTIVE; unselected CHOICE courses must not be created.
6. Repeat the same action and verify duplicates are not created.
7. Verify Course Offering creation works before Student Enrollment exists and does not read or modify Student Enrollment/Student Course Choice records.
8. Verify a different Discipline cannot pull discipline-specific mappings from another Discipline.
9. Verify activation/deactivation and parent ACTIVE guards continue to work.
10. Verify Student Enrollment choices, Curriculum, Batch, Section and Program Offering records are not modified.

### Responsive preview rule
The Add Course Offerings dialog must not require horizontal page/modal scrolling. On desktop it overrides the shared dialog breakpoint width with `sm:max-w-5xl`; the curriculum preview remains `w-full`, wraps long course metadata, and may scroll vertically for long course lists only.

## Delivery list navigation and grouping
- The delivery list is filtered in the existing hierarchy: Academic Session -> College Program Offering -> Batch -> Discipline.
- Academic Session defaults to the linked session marked `is_current`; Program Offering and Batch default to the first applicable option in the selected parent context.
- Discipline filter defaults to All Disciplines.
- Results are not presented as one flat table. They are grouped from existing Curriculum metadata as `Discipline -> Term/Semester -> Specialization (only when present) -> Course Offering`.
- Specialization is never inferred from names/codes and is not duplicated on `course_offerings`; it is read from the existing Curriculum Course Mapping `specialization_id` / Academic Discipline hierarchy.
- A Term/Semester row is navigable/expandable to inspect its Course Offerings. If no specialization exists, courses render directly under the Term.
- Each Term/Semester credit summary is derived from the canonical active Curriculum Slots, not by summing Course Offering rows. MANDATORY Slot = Slot Credits once; CHOICE Slot Required Credits = Slot Credits × Minimum Selection; CHOICE Slot Maximum Credits = Slot Credits × Maximum Selection.
- When Required and Maximum differ, both values are shown. `NON_COUNTABLE` Slots retain their numeric row credit but contribute zero to these countable-credit totals.
- Multiple offered/mapped alternatives in the same Slot do not multiply the Slot credit.
- Discipline summary derives the same Curriculum Slot rules across its applicable terms (common + selected-discipline mappings), independent of how many Course Offering records currently exist.
