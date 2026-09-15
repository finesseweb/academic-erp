# ADR 194 — Reversed Fee Payment Test Cleanup Dependency

**Status:** IMPLEMENTED / OWNER QA PASS / CLOSED  
**Date:** 2026-09-14

## Context
ADR 190 owner QA exposed a Test Data Cleanup dead-end after a Fee Payment had first been reversed through the controlled Payment Reversal workflow.

The reversed receipt no longer appeared under normal Payment Collection because only POSTED receipts are operationally collectible/history-actionable there. Test Data Cleanup also listed only POSTED Fee Payments. However, the REVERSED `fee_payments` row and its immutable `fee_payment_allocations` still correctly remained for audit. An installment schedule cleanup therefore detected those allocation references and blocked deletion with “Clean the related test payment first”, while the related reversed payment was not available to clean.

## Decision
Test Data Cleanup must treat both `POSTED` and `REVERSED` Fee Payments as cleanup-visible test records.

- `feePaymentRows()` lists `POSTED` and `REVERSED` receipts and exposes their real status.
- `cleanupFeePayment()` accepts `POSTED` and `REVERSED` receipts.
- For a `POSTED` receipt, cleanup removes its contribution from installment `paid_amount` before deleting allocations/payment, as before.
- For a `REVERSED` receipt, cleanup **must not** decrement installment `paid_amount` again because controlled reversal already restored it. Cleanup only removes the retained audit allocation/payment rows after normal dependency checks.
- Any linked Refund must still be cleaned first.
- Linked online-payment transaction rows are still removed before the Fee Payment.
- Demand balances are recalculated after cleanup.

## Dependency order
For finance test data, the safe order remains:

`Refund -> Fee Payment (POSTED or REVERSED) -> Installment Schedule -> Fee Demand`

Adjustments remain independently cleanable subject to their own dependencies/recalculation rules.

## Non-goals
- This does not change production payment reversal behavior.
- This does not delete operational audit history outside the explicit Test Data Cleanup tool.
- This does not make REVERSED receipts collectible/refundable again.

## QA gate
Owner must re-test the exact blocked case: clean the REVERSED receipt from Test Data Cleanup, then clean the related Installment Schedule. Both operations must succeed in dependency order without double-decrementing installment paid values or changing authoritative balances incorrectly.


## Owner QA closure — 2026-09-15
Owner confirmed the reversed-payment Test Data Cleanup dependency path works correctly. ADR 194 is **CLOSED / OWNER QA PASS**.
