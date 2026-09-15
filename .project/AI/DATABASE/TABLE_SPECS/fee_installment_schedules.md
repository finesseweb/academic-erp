# Table: `fee_installment_schedules`

## Purpose
Stores data related to the fee installment schedules domain in Academic ERP.

## Database Table
`fee_installment_schedules`

## Columns

- `fee_demand_id` (foreignId)
- `fee_demand_item_id` (foreignId)
- `installment_no` (unsignedSmallInteger)
- `amount` (decimal)
- `due_date` (date)
- `status` (string)
- `created_by` (foreignId)
- `cancelled_by` (foreignId)
- `cancelled_at` (timestamp)
- `cancellation_reason` (string)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.

## Current Finance Link - 2026-09-12

The schedule belongs to a Demand and exact Demand Item. Its due dates define the
collection schedule for that liability without changing the Demand Item's
source academic period or original Standard Due Date snapshot.
