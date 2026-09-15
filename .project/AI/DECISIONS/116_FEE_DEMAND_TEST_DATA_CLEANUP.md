# ADR 116 — Fee Demand Test Data Cleanup

## Status
Accepted — 2026-09-05

## Decision
Applicable Fee Demands are operational test transactions and must be visible as a first-class entity under **Test Data Cleanup → Fee Management**.

Cleanup order is dependency-safe:

`Fee Demand Items → Fee Demand → Admission / Fee Setup`

A test Fee Demand may be removed only while it has no payment, adjustment, waiver, scholarship, installment, refund, or other downstream financial activity. Non-zero `paid_amount` or `adjusted_amount` is treated as financial activity even if a future child table is not present.

Deleting a Fee Demand from Test Data Cleanup deletes its `fee_demand_items` first, then the parent demand, and writes an audit event. It does not delete the Admission, Fee Structure, Fee Head, or Fee Category.

Admission cleanup is blocked while a Fee Demand references that Admission. Fee Structure / Fee Head cleanup remains blocked while Fee Demand Items reference the setup record. Therefore cleanup is performed from downstream operational test data toward upstream setup.

The Full Academic Test Reset includes Fee Demands and Fee Demand Items and deletes them before Admission Confirmation records.

## UI
Fee Management cleanup group order:

1. Fee Demands
2. Fee Structures
3. Fee Heads
4. Fee Categories

This uses the existing Test Data Cleanup UI and confirmation flow; no separate cleanup page or custom styling is introduced.
