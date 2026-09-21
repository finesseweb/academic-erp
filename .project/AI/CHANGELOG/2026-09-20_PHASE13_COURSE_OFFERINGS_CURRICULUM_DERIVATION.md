# 2026-09-20 — Phase 13 Course Offerings curriculum derivation refinement

- Replaced one-course-at-a-time Course Offering creation with Batch + University Discipline + Term bulk derivation.
- Reused existing `curriculum_course_mappings.discipline_id`; no new discipline hierarchy was introduced.
- MANDATORY slots derive common + selected-discipline mappings automatically.
- CHOICE slots derive only mappings selected by ENROLLED students for the Batch/Discipline/Term.
- Added read-only preview of Curriculum credits, credit-counting, slot and rule before creation.
- Bulk create skips existing Batch + Mapping records and creates only missing offerings as INACTIVE.
- No database schema change. Existing Program Offering, Curriculum, Batch, Section and Student Enrollment ownership remains unchanged.
- ADR 209 and Course Offerings PAGE_SPEC updated accordingly.
