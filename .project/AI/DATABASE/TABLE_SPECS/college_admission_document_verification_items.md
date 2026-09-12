# Table: `college_admission_document_verification_items`

## Purpose
Stores data related to the college admission document verification items domain in Academic ERP.

## Database Table
`college_admission_document_verification_items`

## Columns

- `college_id` (unsignedBigInteger)
- `college_admission_application_id` (unsignedBigInteger)
- `status` (enum)
- `notes` (text)
- `finalized_at` (timestamp)
- `finalized_by` (unsignedBigInteger)
- `college_id` (foreign)
- `college_admission_application_id` (foreign)
- `finalized_by` (foreign)
- `college_admission_application_id` (unique)
- `college_admission_document_verification_id` (unsignedBigInteger)
- `college_admission_application_field_value_id` (unsignedBigInteger)
- `college_admission_form_field_id` (unsignedBigInteger)
- `field_label` (string)
- `file_name` (string)
- `remarks` (text)
- `reviewed_at` (timestamp)
- `reviewed_by` (unsignedBigInteger)
- `college_admission_document_verification_id` (foreign)
- `college_admission_application_field_value_id` (foreign)
- `college_admission_form_field_id` (foreign)
- `reviewed_by` (foreign)
- `college_admission_application_field_value_id` (unique)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
