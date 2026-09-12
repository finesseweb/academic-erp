# curriculum_slots

Curriculum-specific academic requirement inside one Term / Semester.

Columns relevant to academic credit:
- `credits` — numeric academic credit value of the Slot
- `credit_counting` — `COUNTABLE` / `NON_COUNTABLE`, default `COUNTABLE`

Rules:
- Credit Treatment belongs to the Slot, not Course Master and not Course Mapping.
- Every Course / Paper mapped to the same Slot inherits the Slot's Credit Treatment.
- `NON_COUNTABLE` keeps the numeric credit visible but excludes it from applicable Required Credits / Maximum Credits and downstream earned / degree-completion totals.
- Slot Clone and Curriculum Amendment preserve Credit Treatment.
- Course Category Group and Credit Treatment are independent.
- SGPA/CGPA inclusion is a separate Result Processing rule.
