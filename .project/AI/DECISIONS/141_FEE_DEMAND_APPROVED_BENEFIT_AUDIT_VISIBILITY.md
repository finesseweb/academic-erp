# ADR 141 — Fee Demand Approved Benefit Audit Visibility

**Date:** 2026-09-08  
**Status:** Accepted / Implemented for QA

## Decision
The Fee Demand register must explain every active reduction caused by an approved Student Scholarship / Concession / Waiver. A reduced outstanding amount must never appear without its financial reason.

Expanded demand details therefore show:
1. Gross Demand (immutable original charge),
2. Paid amount,
3. Scholarship / Concession / Waiver adjustment total,
4. Outstanding amount,
5. each APPROVED Student Benefit using its stored scheme snapshot/code and sanctioned amount, and
6. the approved benefit amount against each affected Fee Demand item.

PENDING, REJECTED and CANCELLED benefits are historical/workflow records and must not be presented as active demand reductions.

## Financial invariant
`Outstanding = Gross Demand - Paid - Approved Adjustments`

Student Benefits never rewrite the original gross demand or original fee-item amount. Removal of an approved benefit follows the existing auditable reversal workflow and the Demand Register must reflect that reversal.
