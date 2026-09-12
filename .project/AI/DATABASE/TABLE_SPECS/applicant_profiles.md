# Table: `applicant_profiles`

## Purpose
Stores data related to the applicant profiles domain in Academic ERP.

## Database Table
`applicant_profiles`

## Columns

- `college_id` (foreignId)
- `registration_enabled` (boolean)
- `email_verification_required` (boolean)
- `captcha_required` (boolean)
- `updated_by` (foreignId)
- `user_id` (foreignId)
- `date_of_birth` (date)
- `phone` (string)
- `lifecycle_status` (enum)
- `student_id` (unsignedBigInteger)
- `student_enabled_at` (timestamp)
- `applicant_user_id` (foreignId)
- `caa_applicant_user_status_idx` (dropIndex)
- `applicant_user_id` (dropConstrainedForeignId)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
