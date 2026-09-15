# ADR 100 — Fee Period Amount Overrides and Curriculum Period Source

## Decision
Recurring Fee Structures keep one default Fee Item amount but may override that amount for individual academic periods. College period availability is derived from the ACTIVE Curriculum Terms attached to the exact College Program Offering; Program Template duration is only the upper academic design boundary.

## Rules
1. `PER_TERM`: each Fee Item stores a default amount. Optional period overrides may set a different amount for an existing academic term.
2. `PER_ACADEMIC_YEAR`: each Fee Item stores a default annual amount. Optional year overrides may set a different amount for an eligible academic-year grouping.
3. College term lists come only from ACTIVE `curriculum_terms` of the exact Program Offering's Curriculum. Fee Management never creates missing semesters/years from Program Template duration.
4. For semester programs an Academic Year is a billing grouping of 2 curriculum terms; for trimester programs it is 3; for year-based programs it is 1. A College Academic Year becomes eligible only when every Curriculum term required for that year exists and is ACTIVE. The final year may contain fewer terms when Program Template duration ends mid-group.
5. Example: Program Template = 6 semesters, but Curriculum currently contains only ACTIVE Semester 1 and Semester 2. College Fee Management exposes Semester 1 and Semester 2 only for `PER_TERM`, and Academic Year 1 only for `PER_ACADEMIC_YEAR`. It must not invent Semester 3–6 or Academic Year 2–3.
6. University structures scoped to one Program Template may define sequence/year overrides against Program Template duration because no single College Curriculum owns University setup. At Fee Demand time, an applicable College demand must still resolve only against actual Curriculum periods of that College Program Offering.
7. Blank period override means use the Fee Item default amount. Example: default ₹20,000; Semester 2 override ₹22,000; Semester 3 override ₹25,000.
8. Changing a Fee Structure's collection basis or academic Program/Offering scope clears old period overrides because their period meaning may no longer be valid.
9. `ONE_TIME`, `SPECIFIC_TERM`, and `SPECIFIC_ACADEMIC_YEAR` do not use recurring period overrides; the Fee Item amount is the exact applicable amount.
10. Fee Demand must later snapshot the resolved amount and exact Curriculum term/year grouping; setup rows remain authoritative configuration and are not mutated by demand generation.

## Data
`fee_structure_item_period_amounts`
- `fee_structure_item_id`
- `period_no`
- `amount`
- actor/timestamps
- unique `(fee_structure_item_id, period_no)`

The meaning of `period_no` is governed by the parent Fee Structure `charge_basis`: term sequence for `PER_TERM`, academic-year number for `PER_ACADEMIC_YEAR`.

## Superseding clarification
ADR 101 adds explicit period applicability. A blank amount override means "use default" only for an applicable period. A period whose Fee Head does not apply is stored as an explicit exclusion, not as zero/near-zero amount.
