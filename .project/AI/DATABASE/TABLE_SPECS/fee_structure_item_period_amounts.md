# Table: `fee_structure_item_period_amounts`

## Purpose
Stores data related to the fee structure item period amounts domain in Academic ERP.

## Database Table
`fee_structure_item_period_amounts`

## Columns

- `fee_structure_item_id` (unsignedBigInteger)
- `period_no` (unsignedTinyInteger)
- `amount` (decimal)
- `created_by` (unsignedBigInteger)
- `updated_by` (unsignedBigInteger)
- `fee_structure_item_id` (foreign)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
