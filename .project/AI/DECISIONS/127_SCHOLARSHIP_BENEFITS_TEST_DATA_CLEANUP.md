# ADR 127 — Scholarship / Benefits Test Data Cleanup

Date: 2026-09-07
Status: Accepted / Implemented — Owner QA required

## Context
Scholarship / Concession / Waiver setup (ADR 126) is new Fee Management test data and must be removable through the existing Test Data Cleanup workflow before scheme QA continues. Cleanup must never remove shared Fee Heads or Reservation Categories merely because a scheme references them.

## Decision
1. Test Data Cleanup → Fee Management exposes `Scholarship / Benefits` as its own cleanup section.
2. Both University-owned and College-owned schemes in the selected University domain are listed.
3. Per-scheme cleanup deletes, in one transaction:
   - `fee_scholarship_scheme_heads` mappings;
   - `fee_scholarship_scheme_categories` mappings;
   - the `fee_scholarship_schemes` parent row.
4. Referenced Fee Heads and Reservation Categories are preserved.
5. Cleanup records audit event `TEST_FEE_SCHOLARSHIP_SCHEME_CLEANED` including deleted mapping counts.
6. Future student/financial operational references (application/allocation/sanction/benefit/adjustment) block scheme cleanup when the corresponding table/column exists.
7. Full Academic Test Reset counts and removes Scholarship/Benefit scheme mappings and schemes in dependency-safe order, before Reservation Category / Fee Head setup can be considered for cleanup.
8. No FK disabling or TRUNCATE is permitted.

## QA
- Create one University scheme and one College scheme.
- Confirm both appear under Test Data Cleanup → Fee Management → Scholarship / Benefits.
- Clean one scheme and confirm only its mappings + parent scheme are removed.
- Confirm Fee Heads and Reservation Categories remain unchanged.
- Confirm success feedback and audit behavior.
- Confirm Full Academic Test Reset preview includes scholarship scheme/mapping counts.
