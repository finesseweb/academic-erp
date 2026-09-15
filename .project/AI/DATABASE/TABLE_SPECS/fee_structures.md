# Table: `fee_structures`

## Purpose
Stores data related to the fee structures domain in Academic ERP.

## Database Table
`fee_structures`

## Columns

- `university_id` (foreignId)
- `college_id` (foreignId)
- `name` (string)
- `code` (string)
- `category` (string)
- `description` (text)
- `is_refundable` (boolean)
- `status` (string)
- `created_by` (foreignId)
- `updated_by` (foreignId)
- `college_program_offering_id` (foreignId)
- `program_template_id` (foreignId)
- `academic_session_id` (foreignId)
- `purpose` (string)
- `currency` (char)
- `notes` (text)
- `fee_structure_id` (foreignId)
- `fee_head_id` (foreignId)
- `amount` (decimal)
- `is_mandatory` (boolean)
- `is_enrollment_clearance_required` (boolean)
- `installment_allowed` (boolean)
- `display_order` (unsignedSmallInteger)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
