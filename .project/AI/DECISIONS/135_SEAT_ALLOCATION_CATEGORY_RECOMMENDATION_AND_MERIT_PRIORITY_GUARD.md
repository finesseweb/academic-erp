# ADR 135 — Seat Allocation Category Recommendation and Merit Priority Guard

## Status
Accepted — 2026-09-07

## Decision
Seat Allocation must preserve the difference between candidate reservation identity and the physical seat bucket while preventing manual counselling from bypassing category entitlement or merit priority.

1. Candidate Reservation Category is resolved from the mapped Admission Form field when available; otherwise an authorized user must confirm it manually.
2. Only ACTIVE VERTICAL reservation categories participate in Candidate Reservation Category selection. Horizontal categories remain separate.
3. For the allocation dialog, the candidate's own configured reserved physical bucket is recommended while it has remaining capacity.
4. If that own reserved bucket is unavailable/full, OPEN / Unreserved is the fallback only when OPEN capacity remains. Backend merit priority remains authoritative.
5. General / Unreserved candidates may consume OPEN only. They may never consume SC/ST/OBC/EWS or another reserved physical bucket.
6. A reserved-category candidate may consume only OPEN or the reserved bucket matching that candidate's category. Cross-category reserved allocation is rejected by the backend.
7. OPEN is category-neutral common merit. A lower-ranked candidate cannot consume an OPEN seat while a higher-ranked SUBMITTED + ELIGIBLE + VERIFIED candidate in the same locked roster remains unallocated.
8. Reserved-seat merit order is protected within the same reservation category: a lower-ranked candidate cannot bypass a higher-ranked verified/eligible unallocated candidate of that category when the higher candidate category is authoritatively mapped.
9. Existing capacity, document verification, single-active-seat, confirmed-admission cancellation, audit and toast rules remain unchanged.

## Rationale
OPEN is not a General-only reservation category. It is the common-merit pool. Candidate identity (GEN/SC/ST/OBC/EWS) therefore cannot be inferred from the physical seat consumed. The UI may recommend a valid seat, but all entitlement and merit-order rules must also be enforced server-side so a manipulated request cannot bypass them.
