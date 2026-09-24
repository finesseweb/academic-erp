# ADR 200 — Student Enrollment Eligibility Queue

Date: 2026-09-16
Status: IMPLEMENTED / OWNER QA REQUIRED
Milestone: ENR-1

## Decision
Enrollment eligibility is a read projection over CONFIRMED Admission + authoritative Programme Offering + authoritative Fee Clearance + existing Student Enrollment state. ENR-1 does not mutate Student lifecycle data.

## Rationale
Separating visibility/eligibility from the ENR-2 enrollment transaction allows the owner to validate cohort scope and financial gating before Student creation is permitted. Fee logic remains owned by `FeeClearanceService`, preventing duplicate or divergent clearance calculations.

## Consequences
- Session/Programme Offering scope is mandatory and College-safe.
- READY/BLOCKED/ENROLLED are derived queue states, not new persisted status columns.
- ENR-2 must re-check all eligibility server-side at transaction time; it may not trust a stale ENR-1 screen.
- ENR-3 identity and ENR-4 import remain out of scope.
