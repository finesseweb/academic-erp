# Table: `fee_payment_refund_allocations`

## Purpose
Exact refund allocation back to the original Payment Allocation and its Demand/Item/Installment/Late Fine source.

## Key columns
`fee_payment_refund_id`, `fee_payment_allocation_id`, `fee_demand_id`, `fee_demand_item_id`, `fee_installment_schedule_id`, `fee_late_fine_charge_id`, `amount`, `sequence_no`, timestamps.

## Contract
No refund allocation may exceed the remaining unrefunded amount of its original payment allocation. Only allocations whose Fee Demand Item snapshot is refundable are eligible.
