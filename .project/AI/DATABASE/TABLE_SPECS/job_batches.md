# Table: `job_batches`

## Purpose
Stores data related to the job batches domain in Academic ERP.

## Database Table
`job_batches`

## Columns

- `queue` (string)
- `payload` (longText)
- `attempts` (unsignedSmallInteger)
- `reserved_at` (unsignedInteger)
- `available_at` (unsignedInteger)
- `created_at` (unsignedInteger)
- `id` (string)
- `name` (string)
- `total_jobs` (integer)
- `pending_jobs` (integer)
- `failed_jobs` (integer)
- `failed_job_ids` (longText)
- `options` (mediumText)
- `cancelled_at` (integer)
- `created_at` (integer)
- `finished_at` (integer)
- `uuid` (string)
- `connection` (string)
- `exception` (longText)
- `failed_at` (timestamp)
- `id`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
