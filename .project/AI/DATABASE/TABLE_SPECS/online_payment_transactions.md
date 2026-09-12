# Table: `online_payment_transactions`

## Purpose
Stores data related to the online payment transactions domain in Academic ERP.

## Database Table
`online_payment_transactions`

## Columns

- `university_id` (foreignId)
- `college_id` (foreignId)
- `college_payment_gateway_id` (foreignId)
- `provider` (string)
- `environment` (string)
- `purpose` (string)
- `amount` (decimal)
- `currency` (string)
- `provider_order_id` (string)
- `provider_payment_id` (string)
- `provider_status` (string)
- `status` (string)
- `reference_no` (string)
- `request_context` (json)
- `response_context` (json)
- `created_by` (foreignId)
- `updated_by` (foreignId)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
