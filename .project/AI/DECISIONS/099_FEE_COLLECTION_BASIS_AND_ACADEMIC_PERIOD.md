# ADR 099 — Fee Collection Basis and Academic Period

## Decision
Fee collection timing is financially configurable, while the academic term model remains authoritative from Program Template → Curriculum → Program Offering.

A Fee Structure stores `charge_basis` as one of:
- `ONE_TIME` — one charge for the applicable structure scope.
- `PER_TERM` — same structure is chargeable for each academic term defined by the program/curriculum.
- `PER_ACADEMIC_YEAR` — same structure is chargeable once per academic-year grouping. Semester programs group 2 terms/year; trimester programs group 3 terms/year; year-based programs already use one term/year.
- `SPECIFIC_TERM` — structure applies only to one academic term sequence. On College structures the selected term must exist and be ACTIVE in the Curriculum attached to the exact Program Offering.
- `SPECIFIC_ACADEMIC_YEAR` — structure applies only to one academic-year grouping.

`charge_period_no` is populated only for the two SPECIFIC modes.

## Invariants
1. Fee Management never changes Program Template `term_structure` or Curriculum terms.
2. A semester-based program remains semester-based even when fees are collected annually or one-time.
3. A year-based program does not expose a parallel semester academic structure in Fee Management.
4. College structures inherit Program Template/Curriculum context from the exact Program Offering.
5. Existing Fee Structures migrate safely as `ONE_TIME` and should be reviewed before production demand generation.
6. Fee Demand must later snapshot both the academic context and collection basis; it must not guess recurrence from Fee Head or Purpose.
7. `installment_allowed` remains an item-level payment rule and is independent from collection basis.

## Examples
- BA: Semester, 6 terms + `PER_TERM` → fee demand once for each Semester 1–6.
- BA: Semester, 6 terms + `PER_ACADEMIC_YEAR` → one fee demand for Year 1 (Sem 1–2), Year 2 (Sem 3–4), Year 3 (Sem 5–6).
- BA: Semester, 6 terms + `ONE_TIME` → one charge; curriculum still remains semester-based.

## Amendment — period amounts and College period source
ADR 100 refines this decision: `PER_TERM` / `PER_ACADEMIC_YEAR` describe recurrence, not a requirement that every period use the same amount. Fee Items have a default amount plus optional period overrides. For College structures, available periods are derived from ACTIVE Curriculum Terms of the exact Program Offering; Program Template duration is only a boundary and does not create missing College terms.
