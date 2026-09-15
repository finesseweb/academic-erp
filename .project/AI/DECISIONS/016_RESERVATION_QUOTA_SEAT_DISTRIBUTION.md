# ADR 016 — Reservation / Quota Uses Effective Admission Seat Buckets

## Decision
Reservation attaches to effective admission seat buckets, not simultaneously to hierarchical parents and children.

Supported buckets:
- PROGRAM
- DISCIPLINE_GENERAL
- SPECIALIZATION

This prevents capacity double counting.

## Category Model
Reservation category names are configurable University data.
Each category is either VERTICAL or HORIZONTAL.

Vertical allocations partition seats.
Horizontal allocations overlay seats.

Open/Unreserved capacity is derived from the unallocated Vertical remainder.

## Student Lifecycle Contract
Future Admission/Student modules must keep physical seat consumption separate from Horizontal quota overlays.

## Dependency Contract
Reservation is downstream of Intake and protects Intake configuration from unsafe mutation once plans exist.

## Merit / quota interaction (2026-09-01)
Merit calculation and Reservation/Quota allocation are separate concerns. The Selection Rule produces a deterministic candidate score; the downstream Roster/Seat Allocation process applies the ACTIVE reservation plan/category rules to that score. Therefore Open/General, vertical reservation categories and horizontal overlays do not require duplicate score formulas by default.

If an institution's approved policy genuinely requires a different scoring formula for a distinct effective admission seat bucket, a separate versioned Selection Rule may be configured for that bucket. Do not hard-code category names or quota-specific formulas into the scoring engine.
