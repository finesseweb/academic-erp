# ADR 154 — Bulk Installment Route Regression Fix

## Status
Accepted — 2026-09-09

## Context
The Bulk Installment UI calls `GET /college/{college}/fee-installments/bulk-preview` to load eligible Fee Demand items and submits schedules to `POST /college/{college}/fee-installments/bulk`.

The controller methods `bulkPreview()` and `bulkStore()` existed, but the current `routes/web.php` registered only the individual installment route. This caused the UI error: `The route college/{college}/fee-installments/bulk-preview could not be found.`

## Decision
Register both Bulk Installment routes explicitly:

- `GET college/{college}/fee-installments/bulk-preview` → `CollegeFeeInstallmentController::bulkPreview`
- `POST college/{college}/fee-installments/bulk` → `CollegeFeeInstallmentController::bulkStore`

The individual installment route remains unchanged.

## Invariants
- No business-rule or database change.
- Existing ADR 152/153 normalized scope logic remains authoritative.
- Route registration must be preserved in future route-file replacements/hotfixes.
- Route cache must be cleared after deployment.
