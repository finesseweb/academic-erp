# Table Spec — college_admission_document_verifications

## Purpose
Application-level Admission document verification gate between Merit / Roster and Seat Allocation.

## Header table: `college_admission_document_verifications`
- `id` PK
- `college_id` FK -> colleges, RESTRICT
- `college_admission_application_id` FK -> college_admission_applications, RESTRICT, UNIQUE
- `status` enum: PENDING / VERIFIED / DEFICIENT
- `notes` nullable text
- `finalized_at` nullable timestamp
- `finalized_by` nullable FK -> users, RESTRICT
- timestamps

## Item table: `college_admission_document_verification_items`
- `id` PK
- `college_admission_document_verification_id` FK -> header, CASCADE
- `college_admission_application_field_value_id` FK -> dynamic field value, RESTRICT, UNIQUE
- `college_admission_form_field_id` FK -> form field, RESTRICT
- `field_label`, `file_name` snapshots
- `status` enum: PENDING / VERIFIED / REJECTED / WAIVED
- `remarks`
- `reviewed_at`, `reviewed_by`
- timestamps

## Downstream relationship
`college_admission_seat_allocations.college_admission_document_verification_id` is a required RESTRICT FK. This makes VERIFIED document approval an explicit prerequisite to physical seat consumption.
