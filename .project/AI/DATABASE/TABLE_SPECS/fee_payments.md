# Table: `fee_payments`

## Purpose
Stores data related to the fee payments domain in Academic ERP.

## Database Table
`fee_payments`

## Columns

- `university_id` (foreignId)
- `college_id` (foreignId)
- `admission_id` (foreignId)
- `academic_session_id` (foreignId)
- `receipt_no` (string)
- `payment_date` (date)
- `amount` (decimal)
- `currency` (string)
- `payment_mode` (string)
- `reference_no` (string)
- `status` (string)
- `notes` (text)
- `collected_by` (foreignId)
- `reversed_at` (timestamp)
- `reversed_by` (foreignId)
- `reversal_reason` (text)
- `fee_payment_id` (foreignId)
- `fee_demand_id` (foreignId)
- `fee_demand_item_id` (foreignId)
- `fee_installment_schedule_id` (foreignId)
- `fee_late_fine_charge_id` (foreignId)
- `source_type` (string)
- `due_date` (date)
- `is_mandatory` (boolean)
- `sequence_no` (unsignedSmallInteger)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.

## Current Collection Contract - 2026-09-12

One posted receipt belongs to the College, Admission, and Academic Session.
Allocation children, rather than the receipt header, preserve the exact Demand,
period-derived due source, and allocation order. Offline and verified online
posting use the same `FeePaymentService` accounting boundary.
