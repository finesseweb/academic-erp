# Table: `college_admission_form_access_roles`

## Purpose
Stores data related to the college admission form access roles domain in Academic ERP.

## Database Table
`college_admission_form_access_roles`

## Columns

- `college_admission_form_access_control_id` (unsignedBigInteger)
- `role_id` (unsignedBigInteger)
- `college_admission_form_access_control_id` (foreign)
- `role_id` (foreign)
- `role_id` (index)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
