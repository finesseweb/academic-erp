# ADR 097 — University / College Fee Structure Applicability

## Status
ACCEPTED / IMPLEMENTED FOUNDATION — 2026-09-04

## Decision
A University Fee Structure does not automatically become compulsory for every College merely because it exists. Each University-owned Fee Structure explicitly declares how matching Colleges must treat it:

- `MANDATORY` — automatically applies to matching Colleges. A College cannot opt out, remove, or modify the University-owned structure. The College may still add its own separate local Fee Structure/charges.
- `OPTIONAL` — a matching College may explicitly adopt the University structure or ignore it and maintain its own local structure.

If the University has not defined a matching Fee Structure for a purpose/session/program scope, the College remains free to define its own Fee Structure. There is no prerequisite requiring a University structure to exist first.

This rule is generic across `ADMISSION`, `ACADEMIC`, `EXAMINATION`, and `OTHER` purposes. It is not hard-coded only for Academic fees.

## Matching scope
A University Fee Structure is relevant to a College only when the College has an ACTIVE Program Offering in the same Academic Session and, when the University structure is Program-specific, for the same Program Template. An `All Programs` University structure may match any ACTIVE College Program Offering in that Academic Session.

## Ownership and immutability
University structures remain University-owned and read-only at College level. College adoption does not copy the structure or its items into College ownership.

Mandatory structures are effective automatically for matching Colleges. Optional structures become effective for a College only when that College records an explicit `ADOPTED` decision. A College may later stop using an optional structure; this does not alter the University source structure.

## College local fees
Mandatory University applicability does not block College-owned additional charges. Future Fee Demand generation will combine:

1. applicable ACTIVE mandatory University structures,
2. applicable ACTIVE optional University structures explicitly adopted by the College, and
3. applicable ACTIVE College-owned structures.

## No-purpose prerequisite
The system must never enforce rules such as “an ADMISSION structure must exist before an ACADEMIC structure” or “University must define a structure before College can define one.” Fee Structure purposes are independent setup classifications.

## Implementation
- Added `fee_structures.college_applicability` for University-owned structures.
- Existing University structures are backfilled as `OPTIONAL` to avoid silently introducing a new compulsory obligation.
- Added `college_fee_structure_adoptions` for explicit College adoption/non-adoption of OPTIONAL University structures.
- Added College permission `college_fee_structure.adopt`.
- University Fee Structure UI now requires `Mandatory` or `Optional` College Applicability.
- College Fee Management shows matching ACTIVE University structures as read-only; mandatory structures show automatic applicability and optional structures expose Adopt / Stop Using actions.

## Future consumer contract
Fee Demand generation must use this ADR as its source of truth. It must not infer College obligation solely from the presence of a University Fee Structure.
