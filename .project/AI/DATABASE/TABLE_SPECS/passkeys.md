# Table: `passkeys`

## Purpose
Stores data related to the passkeys domain in Academic ERP.

## Database Table
`passkeys`

## Columns

- `user_id` (foreignId)
- `name` (string)
- `credential_id` (string)
- `credential` (json)
- `last_used_at` (timestamp)
- `user_id` (index)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
