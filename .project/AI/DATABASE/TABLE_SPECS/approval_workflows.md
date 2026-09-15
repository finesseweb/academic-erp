# Table: `approval_workflows`

## Purpose
Stores data related to the approval workflows domain in Academic ERP.

## Database Table
`approval_workflows`

## Columns

- `university_id` (foreignId)
- `name` (string)
- `code` (string)
- `applies_to` (string)
- `description` (text)
- `status` (enum)
- `created_by` (foreignId)
- `updated_by` (foreignId)
- `approval_workflow_id` (foreignId)
- `sequence_no` (unsignedSmallInteger)
- `approver_role_id` (foreignId)
- `remarks_required_on_reject` (boolean)
- `remarks_required_on_return` (boolean)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
