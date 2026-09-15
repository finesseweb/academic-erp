# ADR 195 — TEST Online Fee Transaction Cleanup Dependency

**Status:** IMPLEMENTED / OWNER QA PASS / CLOSED  
**Date:** 2026-09-14

## Context
A Fee Demand can be referenced by `online_payment_transactions.fee_demand_id` even when no normal Fee Payment / Receipt was ever posted. This occurs when a TEST online fee checkout was initiated, failed, abandoned, or verified but not posted.

The database correctly uses a restrictive foreign key from `online_payment_transactions.fee_demand_id` to `fee_demands.id`. Test Data Cleanup previously did not include that table in the Fee Demand dependency pre-check and did not expose unposted TEST `FEE_PAYMENT` transactions in the cleanup UI. The result was a raw SQL 1451 / HTTP 500 when attempting to delete the Fee Demand.

## Decision
1. Fee Demand cleanup must treat `online_payment_transactions.fee_demand_id` as a downstream financial dependency.
2. Cleanup must fail with a controlled validation message before attempting the delete; a database foreign-key exception must never be the user-facing workflow.
3. The existing **Gateway Test Orders** cleanup category must also expose unposted TEST online fee-payment transactions where:
   - `purpose = FEE_PAYMENT`
   - `environment = TEST`
   - `fee_payment_id IS NULL`
4. Those unposted TEST transactions may be permanently cleaned from that category.
5. A TEST online transaction that has already created a Fee Payment is not independently cleaned there; it is removed through Fee Payment / Receipt cleanup so financial cleanup order remains controlled.

## Cleanup order
For a Fee Demand with online-payment test activity:

`Refund -> Adjustment / dependent records -> Fee Payment / Receipt -> unposted TEST Online Payment Transaction -> Installment Schedule -> Fee Demand`

Only the steps that actually exist for the demand are required.

## QA acceptance
- Attempting to clean a Fee Demand that still has an unposted TEST online-payment transaction must show a controlled dependency message, not a 500 page.
- The transaction must be visible under **Gateway Test Orders**.
- Cleaning that TEST transaction and then cleaning the Fee Demand must succeed once no other dependencies remain.


## Owner QA closure — 2026-09-15
Owner confirmed the TEST online-payment dependency cleanup path is working in the final Finance closure QA. ADR 195 is **CLOSED / OWNER QA PASS**.
