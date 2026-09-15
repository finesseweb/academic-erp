# Table: `fee_late_fine_charges`

## Purpose
Stores data related to the fee late fine charges domain in Academic ERP.

## Database Table
`fee_late_fine_charges`

## Columns

- `university_id` (foreignId)
- `college_id` (foreignId)
- `academic_session_id` (foreignId)
- `college_program_offering_id` (foreignId)
- `fee_head_id` (foreignId)
- `name` (string)
- `code` (string)
- `source_type` (string)
- `calculation_type` (string)
- `frequency` (string)
- `value` (decimal)
- `grace_days` (unsignedSmallInteger)
- `maximum_fine_amount` (decimal)
- `status` (string)
- `notes` (text)
- `created_by` (foreignId)
- `updated_by` (foreignId)
- `fee_late_fine_rule_id` (foreignId)
- `fee_demand_id` (foreignId)
- `fee_demand_item_id` (foreignId)
- `fee_installment_schedule_id` (foreignId)
- `due_date` (date)
- `calculated_as_of` (date)
- `overdue_days` (unsignedInteger)
- `base_outstanding_amount` (decimal)
- `fine_amount` (decimal)
- `superseded_by_id` (unsignedBigInteger)
- `calculated_by` (foreignId)
- `superseded_at` (timestamp)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.

## Current Finance Link - 2026-09-12

The charge references its Demand Item and optional Installment schedule, and
stores the exact `due_date` used to calculate overdue days. The source date is
therefore traceable to the Academic-Period-validated Demand snapshot or its
explicit installment schedule.
