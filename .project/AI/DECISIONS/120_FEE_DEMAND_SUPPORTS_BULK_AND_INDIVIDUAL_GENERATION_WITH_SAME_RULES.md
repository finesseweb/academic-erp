# ADR 120 — Fee Demand supports Bulk and Individual generation with the same rules

## Decision
Routine period Fee Demand supports two operator modes under the same Fee Setup and eligibility context:
- `Bulk Cohort` — generate for the full currently eligible cohort.
- `Individual` — generate for one selected candidate/student from that same eligible cohort.

Individual generation is not a bypass, override, or separate fee-calculation path.

## Rules
- Program Offering, Demand Purpose, Collection Basis and Billing Period are still derived from the effective Fee Setup.
- Bulk and Individual modes use the same applicable University + College structures, exact period amounts/policies, and demand-item snapshot rules.
- Both modes use the same eligibility gate. If a billing context is blocked (for example Semester 2 before authoritative Academic Progression exists), Individual generation is also blocked.
- Before Student Enrollment/Progression exists, the first Academic period uses CONFIRMED admissions as the eligible cohort. Later periods will consume authoritative Student Enrollment + Academic Progression output when implemented.
- Individual generation currently selects one CONFIRMED admission from the selected Program Offering for the first Academic period.
- Existing active demand items are never duplicated. If an individual charge already exists, Individual generation returns no new applicable item; if an individual demand was created first, a later Bulk run skips those already-demanded source items for that candidate.
- `generation_mode=INDIVIDUAL_PERIOD` identifies individually generated routine period demands. `BULK_PERIOD` remains the provenance for cohort generation.
- No new RBAC permission is introduced; existing `college_fee_demand.generate` authorizes both modes.
- No database migration is required for this change because `generation_mode` is already persisted as a string value.

## QA
1. Select an ACTIVE Program Offering, `ACADEMIC`, and first available Semester/Academic-Year context.
2. Switch Generate For to `Individual`; only CONFIRMED admissions from the selected offering must be selectable.
3. Generate for one candidate. Verify exact Fee Setup snapshots including amount, Mandatory, Enrollment Clearance, Installment and Refundable.
4. Attempt the same Individual generation again. Expected: no duplicate active fee item/demand is created.
5. Clean/reset as needed, generate Individual first, then run Bulk for the same context. Expected: that candidate's already-demanded source items are skipped while other eligible cohort members are generated.
6. Generate Bulk first, then attempt Individual for the same candidate/context. Expected: no duplicate active fee item is created.
7. Select Semester/Year 2 while Academic Progression is unavailable. Both Bulk and Individual actions must remain blocked.
