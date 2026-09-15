# Table: `fee_structure_item_period_settings`

## Purpose
Stores data related to the fee structure item period settings domain in Academic ERP.

## Database Table
`fee_structure_item_period_settings`

## Columns

- `fee_structure_item_id` (unsignedBigInteger)
- `period_no` (unsignedTinyInteger)
- `is_mandatory` (boolean)
- `is_enrollment_clearance_required` (boolean)
- `installment_allowed` (boolean)
- `display_order` (unsignedSmallInteger)
- `status` (enum)
- `created_by` (unsignedBigInteger)
- `updated_by` (unsignedBigInteger)
- `fee_structure_item_id` (foreign)
- `created_by` (foreign)
- `updated_by` (foreign)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.

## Current Period Contract - 2026-09-12

- Also stores nullable `due_date`, the Standard Due Date for this Fee Head/Billing Period.
- Unique business key: (`fee_structure_item_id`, `period_no`).
- `period_no` maps to a Curriculum Term sequence for `PER_TERM`, or a derived group of Terms for `PER_ACADEMIC_YEAR`.
- ACTIVE recurring settings require a due date, validated inside the corresponding University Calendar Academic Period coverage.
- This table snapshots Fee Setup policy only; Demand generation copies the effective values to `fee_demand_items`.
