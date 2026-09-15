# ADR 094 — Curriculum Slot Credit Counting

## Decision
Credit Treatment belongs to the **Curriculum Slot**, not to an individual Curriculum Course / Paper Mapping.

Each Curriculum Slot owns:
- `credits` — the academic credit value shown for that Slot
- `credit_counting` — `COUNTABLE` or `NON_COUNTABLE`

Default is `COUNTABLE`.

## Why Slot owns the rule
The Slot defines the academic requirement in a specific Curriculum + Term / Semester. All Courses / Papers mapped into that Slot satisfy the same requirement. Therefore countability must remain stable regardless of which compatible Course is mapped or selected.

Keeping Credit Treatment on a Course Mapping could allow different mapped Courses inside the same Slot to have conflicting credit behavior and would make Term required-credit totals ambiguous.

Course Master remains reusable and does not own this rule.

## Meaning
`NON_COUNTABLE` does **not** mean zero-credit. The Slot still keeps and displays its numeric academic credit value. It means that value is excluded from applicable required / earned / degree-completion credit totals.

Example: a Slot may be `4.00` credits and `NON_COUNTABLE`. The learner still studies a 4-credit academic requirement, but those 4 credits do not contribute to the applicable degree-credit total.

## Rules
- New and edited Slots explicitly carry `COUNTABLE` or `NON_COUNTABLE`.
- Existing Slots default to `COUNTABLE`.
- The corrective migration preserves the earlier temporary mapping-level values: if any existing mapping in a Slot was marked `NON_COUNTABLE`, that Slot is migrated to `NON_COUNTABLE`.
- Mapping-level `credit_counting` is removed.
- Slot Clone and Curriculum Amendment preserve Credit Treatment.
- Curriculum Credit Summary excludes `NON_COUNTABLE` Slots from Required Credits and Maximum Credits while retaining the Slot's displayed numeric credit.
- Credit Treatment remains independent from Course Category Group (`Core`, `Elective`, `Requirement`, `Other`).
- Credit Treatment does not decide SGPA/CGPA inclusion. GPA participation remains a separate Result Processing academic rule.

## Downstream contract
Student Enrollment / Lifecycle, Result Processing and Degree/Completion logic must consume the Slot-level Credit Treatment and must never assume every numeric Slot credit contributes to the degree-credit total.
