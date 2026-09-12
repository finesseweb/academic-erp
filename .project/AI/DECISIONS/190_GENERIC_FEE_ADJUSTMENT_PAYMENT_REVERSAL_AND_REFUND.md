# ADR 190 — Generic Fee Adjustment, Payment Reversal and Refund

**Status:** IMPLEMENTED / OWNER QA REQUIRED  
**Date:** 2026-09-12

## Decision
Implement the next frozen Fee Phase workflow as three separate auditable financial operations:

1. **Manual Adjustment** — CREDIT lowers student principal liability; DEBIT increases it. It never rewrites the immutable gross Fee Demand Item.
2. **Payment Reversal** — corrects an entire erroneous POSTED receipt. All original allocations remain for audit, the receipt becomes REVERSED, installment paid values are restored, and Demand paid/outstanding is recalculated.
3. **Refund** — returns part/all of money previously paid. Refund allocation is restricted to the original Fee Demand Item snapshot `is_refundable = true`; non-refundable allocations are never consumed by the refund allocator.

## Persistence
- `fee_adjustments`
- `fee_payment_refunds`
- `fee_payment_refund_allocations`

Refund allocation points back to the original `fee_payment_allocation_id`, preserving exact Demand Item / Installment / Late Fine provenance.

## Balance contract
Gross demand remains immutable.

`Net Adjusted = Approved Benefits + Posted CREDIT Adjustments - Posted DEBIT Adjustments`

`Net Paid Principal = POSTED principal Payment Allocations - POSTED principal Refund Allocations`

`Outstanding = max(Gross Demand - Net Paid Principal - Net Adjusted, 0)`

A payment reversal removes that receipt from POSTED paid principal by status; its original ledger credit remains visible and an equal reversal debit is emitted.

## Installments
Generic adjustments rebalance ACTIVE installment amounts proportionally through the existing installment service. Already-paid installment values are floors and are never reduced below paid amount. Refund/reversal restores installment `paid_amount` by the exact refunded/reversed allocation amount.

## Ledger
ADR 189 Student Fee Ledger is extended, not replaced. It now shows Adjustment, Adjustment Reversal, Payment Reversal and Refund rows in chronological running balance.

## RBAC
- `college_fee_adjustment.view`
- `college_fee_adjustment.post`
- `college_fee_adjustment.reverse`
- `college_fee_refund.post`

All are College-scoped and College-delegable; mutating permissions are sensitive.

## Test Data Cleanup
Adjustments and Refunds are independently visible/cleanable. A test Payment with Refund children must have those Refunds cleaned first. Cleanup restores installment paid values and recalculates Demand balances.

## Explicit exclusions
- No automatic provider-side LIVE gateway refund API is introduced by this ADR.
- Refund `GATEWAY` mode records the ERP-side refund only; provider refund automation requires a later provider-specific ADR and QA.
- No Fee Clearance or Student Enrollment behavior is introduced here.

## Next gate
Owner QA must pass before implementing Fee Clearance.
