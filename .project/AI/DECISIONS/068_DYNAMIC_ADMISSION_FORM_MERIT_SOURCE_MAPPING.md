# ADR 068 — Dynamic Admission Form Merit Source Mapping

## Decision
Selection Rule Merit scoring may be sourced dynamically from one or more numeric Admission Form field pairs instead of requiring duplicate manual score entry.

## Contract
- Mapping is configured only on an INACTIVE Selection Rule version.
- Each source has a label, Obtained field ID, Maximum field ID and weight within Merit.
- Only ACTIVE `NUMBER` fields from an ACTIVE Admission Form mapping for the same Program Offering are eligible.
- Mapped source weights total exactly 100%.
- Multiple sources are supported, e.g. Class 10 = 40% and Class 12 = 60%.
- No source mapping means existing manual Merit Score Capture remains available.
- Submitted applications consume the exact locked Selection Rule version; later rule/form changes do not rewrite historical calculations.
- Score calculation snapshots the field IDs, raw/max values, normalized values and source weights.

## Reservation / quota
The Merit formula remains score-oriented. Reservation/Quota/Roster applies downstream to qualified/ranked candidates. This avoids duplicating merit formulas for Open/OBC/SC/ST/EWS etc. unless an approved policy genuinely requires different rules for distinct effective admission seat buckets.

## Example
Class 10: 400/500 = 80, source weight 40%.
Class 12: 450/500 = 90, source weight 60%.
Composite Merit = 80×0.40 + 90×0.60 = 86/100.
