# ADR 153 — Bulk Installment Scope aggregation must be ONLY_FULL_GROUP_BY safe

## Status
Accepted — 2026-09-09

## Context
ADR 152 normalized legacy and Admission Initial Fee Demand context for Bulk Installment scope discovery. The first implementation grouped directly by repeated CASE expressions that referenced raw `fee_demands.demand_context` and `billing_basis_group` columns.

On MySQL with `ONLY_FULL_GROUP_BY` enabled, the scope query failed with SQLSTATE 42000 / error 1055 because MySQL still treated those raw column references inside the nested CASE expression as non-grouped selected dependencies.

## Decision
Bulk Installment scope discovery now uses a two-stage query:

1. Inner query: normalize each eligible Fee Demand Item into `normalized_purpose`, `normalized_basis_group`, and `normalized_period_no`.
2. Outer query: aggregate only by those normalized aliases and calculate cohort/item counts and display label.

No business rule changes. This is a SQL-compatibility correction only.

## Invariants preserved
- CANCELLED Fee Demands remain excluded.
- Only Fee Demand Items with `installment_allowed = true` are considered.
- Admission Initial automatic demands remain discoverable.
- Legacy Academic/Term demand context remains normalized.
- Candidate loading continues to use the same ADR 152 normalization rules.
- No schema migration is required.
