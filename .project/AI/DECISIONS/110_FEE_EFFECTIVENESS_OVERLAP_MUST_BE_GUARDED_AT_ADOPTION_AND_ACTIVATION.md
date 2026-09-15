# ADR 110 — Fee overlap invariant is enforced when structures become effective

## Status
Accepted — 2026-09-05

## Context
ADR 109 blocked a College user from adding the same Fee Head while a matching University Fee Structure was already effective. Owner QA exposed a lifecycle bypass:
1. stop using an OPTIONAL University structure,
2. configure the same Fee Head in an INACTIVE College structure,
3. adopt the University structure again,
4. activate the College structure.

Both structures could then become effective and charge the same Fee Head over the same billing coverage.

## Decision
The no-double-charge rule is an **effectiveness invariant**, not only an item-save validation.

The ERP must enforce it in every direction where a structure becomes effective:
- College Fee Structure activation must be blocked when an effective University structure (MANDATORY or adopted OPTIONAL) has the same Fee Head over overlapping academic coverage.
- OPTIONAL University adoption must be blocked when an already ACTIVE matching College Fee Structure has the same Fee Head over overlapping academic coverage.
- University Fee Structure activation/re-activation must be blocked when it would become effective for a College and conflict with an ACTIVE College structure. This includes MANDATORY structures and OPTIONAL structures with an existing ADOPTED record.

An INACTIVE College structure may coexist as draft/setup with an adopted University structure. The conflict becomes blocking only when both sides would be effective.

Overlap is calculated from ACTIVE, positive-value charges and underlying academic term coverage. ONE_TIME overlaps ONE_TIME within the same purpose; term/year/specific-period structures are compared by their underlying term coverage.

## Consequence
No lifecycle sequence may leave University and College Fee Structures simultaneously effective with the same Fee Head over overlapping billing coverage for the same fee purpose.
