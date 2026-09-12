# Table: `sessions`

## Purpose
Stores data related to the sessions domain in Academic ERP.

## Database Table
`sessions`

## Columns

- `name` (string)
- `email` (string)
- `email_verified_at` (timestamp)
- `password` (string)
- `token` (string)
- `created_at` (timestamp)
- `id` (string)
- `user_id` (foreignId)
- `ip_address` (string)
- `user_agent` (text)
- `payload` (longText)
- `last_activity` (integer)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
