# ADR 119 — Fee Demand snapshots Fee Head refundability

## Decision
`fee_heads.is_refundable` is a financial policy input and must be copied to `fee_demand_items.is_refundable` when a demand item is generated.

The demand item snapshot is authoritative for downstream payment/refund/reversal processing. Editing the Fee Head later must not silently alter refundability of an already-issued demand.

## Rules
- Refundability originates from the applicable Fee Head.
- Every newly generated demand item snapshots `is_refundable` together with amount, mandatory, enrollment-clearance and installment policy.
- Period-specific amount/policy does not redefine refundability; refundability remains a Fee Head property at generation time.
- Existing demand items created before this column are backfilled once from their currently referenced Fee Head during migration.
- Demand detail must expose `Refundable` / `Non-refundable` read-only status.
- A refundable snapshot does not itself authorize a refund. Future refund processing must also consider paid amount, prior refunds/reversals and the applicable refund/cancellation policy.
