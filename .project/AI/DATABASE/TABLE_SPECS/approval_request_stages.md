# Table: `approval_request_stages`

## Purpose
Stores data related to the approval request stages domain in Academic ERP.

## Database Table
`approval_request_stages`

## Columns

- `approval_status` (string)
- `approval_workflow_id` (foreignId)
- `university_id` (foreignId)
- `subject_type` (string)
- `subject_id` (unsignedBigInteger)
- `status` (enum)
- `current_stage_sequence` (unsignedSmallInteger)
- `submitted_by` (foreignId)
- `submitted_at` (timestamp)
- `completed_at` (timestamp)
- `approval_request_id` (foreignId)
- `sequence_no` (unsignedSmallInteger)
- `name` (string)
- `approver_role_id` (foreignId)
- `decided_by` (foreignId)
- `remarks` (text)
- `decided_at` (timestamp)
- `remarks_required_on_reject` (boolean)
- `remarks_required_on_return` (boolean)
- `curricula_approval_status_idx` (dropIndex)
- `approval_status` (dropColumn)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
