# curriculum_course_mappings

Contextual Curriculum binding for reusable Course / Subject Master.

Columns:
- id
- curriculum_slot_id
- course_id
- discipline_id — application-required for new mappings
- specialization_id — optional
- display_order
- status
- created_by / updated_by
- timestamps

Rules:
- Discipline must be an active same-University top-level Discipline mapped to the Curriculum Program Template.
- Specialization is optional; when present it must be active, same-University, mapped under the exact Program Template Discipline, and its parent must be the selected Discipline.
- Course remains reusable and independent; it must be active, same-University, and match Slot Course Category + Course Type.
- same Course cannot be duplicated in the same Slot.

Database upgrade keeps Discipline/Specialization nullable for historical pre-feature rows.
