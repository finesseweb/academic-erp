# ADR 155 — Student Benefit Bulk Route Regression Fix

## Status
Accepted — 2026-09-09

## Context
The Student Benefit bulk workflow is part of the implemented Fees Phase and its controller methods (`bulkSchemes`, `bulkCandidates`, and `bulkStore`) already exist. A later `routes/web.php` replacement that restored Bulk Installment routes accidentally dropped the three Student Benefit bulk routes. The UI therefore failed with `college/{college}/fee-student-benefits/bulk-candidates could not be found` even though the feature and controller logic were still present.

## Decision
Restore the complete Student Benefit bulk route contract while preserving all Fee Installment routes introduced by ADR 154. The following routes are mandatory whenever Student Benefits bulk assignment is enabled:

- `GET college/{college}/fee-student-benefits/bulk-schemes` → `bulkSchemes`
- `GET college/{college}/fee-student-benefits/bulk-candidates` → `bulkCandidates`
- `POST college/{college}/fee-student-benefits/bulk` → `bulkStore`

No benefit eligibility, scheme applicability, approval, category snapshot, Fee Demand linkage, or financial calculation rule changes in this ADR. Bulk assignment remains controlled selection, not automatic entitlement, and every selected student is revalidated server-side at submission.

## Project consistency rule
`routes/web.php` is a shared integration file. Future patches that replace it must preserve the full existing route contract for already-implemented modules. A route hotfix for one module must not regress another module. Before shipping a replacement `routes/web.php`, verify at minimum the route families touched during the current Fees/Admission work.

## QA
Run:

```bash
php artisan optimize:clear
php artisan route:list --path=fee-student-benefits
php artisan route:list --path=fee-installments
```

Expected Student Benefit bulk routes and Fee Installment bulk routes must coexist. Then test Bulk Assignment → select scheme → eligible students load → select students → assign selected benefits.

## Migration
No database migration required.
