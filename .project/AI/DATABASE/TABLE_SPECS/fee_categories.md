# Table: `fee_categories`

## Purpose
Stores data related to the fee categories domain in Academic ERP.

## Database Table
`fee_categories`

## Columns

- `university_id` (foreignId)
- `college_id` (foreignId)
- `name` (string)
- `code` (string)
- `description` (text)
- `display_order` (unsignedSmallInteger)
- `status` (string)
- `created_by` (foreignId)
- `updated_by` (foreignId)
- `fee_category_id` (unsignedBigInteger)
- `fee_category_id` (foreign)
- `fee_category_id` (index)
- `category` (dropColumn)
- `category` (string)
- `fee_heads_category_fk` (dropForeign)
- `fee_heads_category_idx` (dropIndex)
- `fee_category_id` (dropColumn)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
