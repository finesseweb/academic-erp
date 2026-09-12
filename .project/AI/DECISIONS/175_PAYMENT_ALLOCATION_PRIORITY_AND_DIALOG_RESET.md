# ADR 175 — Payment Allocation Priority + Dialog State Reset

## Status
Implemented for QA on 2026-09-10. No migration.

## Context
ADR 171/173 payment QA exposed two defects during a mixed mandatory + optional + future-principal payment:
1. Candidate sorting used due date before category priority, so an earlier-dated optional liability could consume payment before a selected mandatory principal.
2. After a successful payment and Inertia refresh, reopening the same Collect Payment dialog could retain the previously entered amount even though the selected available balance had changed.

## Decision
Payment allocation priority is now category-first across the entire selected payable set:
1. Mandatory Principal
2. Mandatory Late Fine
3. Optional Principal
4. Optional Late Fine

Inside each category, oldest due date is allocated first, followed by stable Fee Demand Item ordering. `include_optional`, `include_late_fine`, and `include_future` continue to control which candidates are eligible before this ordering is applied.

This means a selected future mandatory principal is still mandatory and is consumed before any optional principal when `Include future dues` is explicitly enabled. Optional liabilities never consume money ahead of selected mandatory liabilities.

## Dialog reset rule
Every time Collect Payment is opened, the dialog resets to the current server-derived state:
- Optional charges: excluded
- Late Fine: included
- Future dues: excluded
- Payment Amount: current `collectable_default`
- Payment Date: current date
- Payment Mode: Cash
- Reference/Notes: blank
- Previous validation errors: cleared

This prevents stale posted amounts from surviving a successful payment refresh/reopen.

## Invariants preserved
- Fee Demand and installment liability amounts are not edited by Payment Collection.
- Partial payment remains allowed.
- Overpayment remains blocked atomically.
- Future principal remains excluded unless explicitly selected.
- Optional charges remain excluded unless explicitly selected.
- Gross Fee Demand remains unchanged by payment.
- Exact allocation rows remain auditable.

## QA regression case
For a selected payable set containing Mandatory/Future Principal ₹20,000 and Optional Principal ₹2,000, posting ₹21,000 must result in:
- Mandatory Principal paid: ₹20,000
- Optional Principal paid: ₹1,000
- Optional Principal remaining: ₹1,000
- Mandatory Principal remaining: ₹0

Reopening the dialog after posting must show Payment Amount equal to the newly selected available amount, not the previous ₹21,000.
