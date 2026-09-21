# ADR 209 — Course Offering Delivery Boundary

Status: ACCEPTED / IMPLEMENTED — OWNER QA REQUIRED
Date: 2026-09-20

## Context
Phase 13 begins Course Delivery after Student Enrollment. Existing architecture already owns Program Offering, Curriculum, Batch and Section. Course Delivery needs an operational record representing that a Curriculum course is actually available for teaching to a Batch without duplicating those masters.

## Decision
Introduce `course_offerings` as the bridge:

`Batch + Curriculum Course Mapping -> Course Offering`

A Course Offering is batch-level. It does not store Section. Section remains a child of Batch and will be consumed by later Faculty Allocation/Class Scheduling where section-specific delivery is actually needed.

The Curriculum Course Mapping must belong to the exact Curriculum inherited from the Batch's parent College Program Offering.

## Consequences
- Program Offering continues to answer which Program/Curriculum/Session a College runs.
- Curriculum continues to answer what courses/slots/terms define the academic programme.
- Batch continues to answer which operational student cohort exists.
- Section continues to partition a Batch.
- Course Offering answers which curriculum course is enabled for operational delivery to that Batch.
- Faculty Allocation can safely reference Course Offering next without redefining course/program/curriculum ownership.

## Rejected alternatives
1. Add delivery fields to `college_program_offerings`: rejected because Program Offering is programme/session scope, not course delivery scope.
2. Add `section_id` to Course Offering: rejected for this milestone because it would duplicate identical offerings across Sections and prematurely merge Course Offering with class scheduling.
3. Store direct `course_id`, `curriculum_id`, `college_id`, or session/program fields: rejected because they duplicate authoritative upstream relationships and permit drift.

## Authorization
College-scoped RBAC is server-authoritative. React visibility is convenience only.

## 2026-09-20 refinement — curriculum-driven bulk creation
The creation workflow is not an arbitrary one-course-at-a-time picker. For a selected Batch, the operator selects an existing University-defined Discipline and Curriculum Term. The system derives delivery mappings from the authoritative Curriculum and creates the applicable Course Offerings in bulk.

Rules:
- `academic_disciplines` and `curriculum_course_mappings.discipline_id` are reused; no College-only discipline master is introduced.
- MANDATORY slots contribute all applicable common mappings (`discipline_id IS NULL`) plus mappings for the selected Discipline.
- CHOICE slots contribute only mappings already selected by ENROLLED students of that Batch + Discipline for the selected Term. Re-running creation safely adds newly-required choices and skips existing offerings.
- Slot `credits` and `credit_counting` are University Curriculum facts. Course Offering displays them read-only and does not duplicate or override them.
- `course_offerings` schema remains unchanged; the refinement changes derivation/workflow, not ownership.

## 2026-09-20 correction — Course Offering precedes Student Enrollment
Course Offering is a delivery-planning concern and MUST NOT depend on Student Enrollment existing first. The earlier refinement that derived CHOICE mappings only from `student_enrollment_course_choices` is superseded.

Authoritative creation rules:
- The selected Batch determines the authoritative University Curriculum through its existing Program Offering.
- The operator selects an existing University-defined Discipline and active Curriculum Term.
- All active applicable MANDATORY mappings (common + selected Discipline) are automatically included and cannot be deselected.
- All active applicable CHOICE mappings (common + selected Discipline) are displayed before Student Enrollment and may be selected by the College for delivery.
- Student enrollment/choice counts are not a prerequisite and are not consulted when creating Course Offerings.
- Credits and `credit_counting` remain read-only University Curriculum facts.
- No schema, hierarchy, Batch, Section, Curriculum, or Student Enrollment ownership is changed.

## Curriculum credit summary linkage correction — 2026-09-20
Course Delivery must not derive semester or discipline academic credit totals by summing Course Offering rows. Course Offering is a delivery instance and is not the academic credit authority.

All displayed Curriculum credit totals are derived from the canonical active `curriculum_slots` represented by the loaded Curriculum mappings, following the existing Curriculum Credit Summary contract:
- one active `MANDATORY` Slot contributes its Slot credits once;
- one active `CHOICE` Slot contributes `credits × min_selection` to Required Credits and `credits × max_selection` to Maximum Credits;
- `NON_COUNTABLE` Slots retain their numeric credit on course rows but contribute zero to required/maximum countable-credit totals;
- multiple Course mappings inside the same Slot never multiply the Slot credit by the number of offerings/mappings;
- Course Offering does not persist, override, or recalculate its own academic credit rule.

Therefore College delivery may offer multiple alternatives from one Choice Slot while the semester summary continues to show the University Curriculum's required/maximum credit load.
