# ADR 165 — Test Data Cleanup Bulk Route Regression Fix

## Status
Implemented — QA pending.

## Problem
The Test Data Cleanup UI introduced in ADR 148 submits both **Clean Selected** and **Clean All Cleanable** actions to:

`POST /admin/system-maintenance/test-data-cleanup/bulk-clean`

A later shared `routes/web.php` replacement preserved most cleanup routes but dropped this bulk route. The UI therefore returned HTTP 404 for bulk cleanup actions even though the controller implementation remained available.

## Decision
Restore the canonical route contract:

- `POST admin/system-maintenance/test-data-cleanup/bulk-clean`
- controller: `TestDataCleanupController@bulkCleanup`
- route name: `test-data-cleanup.bulk-clean`

The route is declared **before** the generic `{type}/{id}` cleanup route to keep the contract unambiguous.

## Preservation requirement
Any future replacement of `routes/web.php` must preserve all existing module route contracts, including at minimum:

- Test Data Cleanup bulk cleanup
- Student Benefit bulk routes
- Fee Installment bulk routes
- Late Fine routes
- Academic Calendar curriculum-term period routes
- Direct Admission downstream routes

Shared route-file replacement is treated as a regression-sensitive operation and must be checked against already-implemented route contracts.

## Business/Data impact
No cleanup behavior, dependency guard, deletion policy, RBAC rule, or data model changes. This ADR only restores the missing HTTP route used by the existing cleanup UI/controller.

## QA
1. Run `php artisan optimize:clear`.
2. Open System Maintenance → Test Data Cleanup.
3. Use **Clean Selected** on a cleanable record and confirm no 404.
4. Use **Clean All Cleanable** for a module, enter the exact confirmation phrase, and confirm no 404.
5. Verify protected/dependency-blocked records remain protected.
6. Spot-check individual **Clean** actions still work.
