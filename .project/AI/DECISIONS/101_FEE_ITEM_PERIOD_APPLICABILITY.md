# ADR 101 — Fee Item Period Applicability

## Decision
Recurring Fee Items must model two independent period-level facts: whether the Fee Head is applicable in that academic period, and what amount applies when it is applicable.

## Rules
1. A recurring Fee Item keeps a positive default amount as the fallback rate.
2. Every available billing period is applicable by default unless an explicit exclusion exists.
3. An applicable period may have an optional amount override; blank override means use the default amount.
4. A Not Applicable period means the Fee Head does not exist for that period. Future Fee Demand generation must create no demand line for that Fee Head/period. It is not represented as a zero-value demand.
5. At least one available period must remain applicable for an ACTIVE recurring Fee Item configuration.
6. College period choices continue to come only from ACTIVE Curriculum Terms attached to the exact College Program Offering. Program Template duration is an upper design boundary and must not manufacture College periods.
7. Academic-year billing groups are derived by Curriculum term sequence, not free-text term names: Semester programs group sequences 1+2, 3+4, etc.; Trimester programs group 1+2+3, 4+5+6, etc.; Year-based programs group one Curriculum term per academic year. A group is exposed only when all required Curriculum terms for that group exist and are ACTIVE.
8. Example: Program Template says 6 semesters but Curriculum has only ACTIVE Semester 1 and Semester 2. The College can configure only those two semester periods, and only Academic Year 1 is available. Semester 3–6 / Academic Year 2–3 are not invented.
9. If Semester 1 Tuition is ₹20,000 and Semester 2 has no Tuition Fee, Semester 1 remains applicable and Semester 2 is explicitly Not Applicable. The system must not use ₹0 or ₹0.01 as a workaround.
10. Changing Fee Structure collection basis or Program/Offering scope clears both period amount overrides and period exclusions because the period meaning may have changed.

## Data
`fee_structure_item_period_exclusions`
- `fee_structure_item_id`
- `period_no`
- `created_by`
- timestamps
- unique `(fee_structure_item_id, period_no)`

Existing `fee_structure_item_period_amounts` remains the period-specific rate override table. Absence from both child tables means the period is applicable and uses the parent Fee Item default amount.
