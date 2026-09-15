# ADR 111 — Fee Item removal is billing-period scoped for recurring structures

## Status
Accepted — 2026-09-05

## Context
Fee configuration is period-first (`Fee Structure → Billing Period → Fee Items`). A recurring Fee Head is represented by one shared `fee_structure_items` row with period-specific amounts/settings and explicit period exclusions. Users need a safe way to remove a charge after adding it to a billing period.

## Decision
- INACTIVE Fee Structures expose a Remove/Delete action beside each configured Fee Item.
- ACTIVE Fee Structures remain immutable; deactivate first.
- For `PER_TERM` / `PER_ACADEMIC_YEAR`, removal applies only to the selected billing period:
  - delete that period's amount and policy setting;
  - persist the period as excluded;
  - keep the shared Fee Item if it is still configured in another period.
- If no recurring billing period remains applicable, delete the shared Fee Item after deleting its child rows.
- For `ONE_TIME`, `SPECIFIC_TERM`, and `SPECIFIC_ACADEMIC_YEAR`, removal deletes the Fee Item because the structure has only one effective configured billing period for that item.
- University and College use the same behavior and existing Fee Structure update permission; no new RBAC permission is introduced.
- University-owned structures shown to a College remain read-only and do not expose this action in the College view.
- Every removal is audited.

## Consequences
The UI mental model stays consistent: a Fee Head visible inside a Billing Period is applicable there; removing it removes only that period's charge unless it is the final/only applicable period.
