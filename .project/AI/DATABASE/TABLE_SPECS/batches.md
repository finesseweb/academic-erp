# Table Spec — `batches`

Status: IMPLEMENTED — OWNER QA REQUIRED
Date: 2026-09-04

## Purpose
Operational College cohort under one exact `college_program_offerings` record. It does not own seat capacity or reservation logic.

## Columns
- `id` BIGINT PK
- `college_program_offering_id` BIGINT NOT NULL FK -> `college_program_offerings.id` RESTRICT
- `code` VARCHAR(50) NOT NULL
- `name` VARCHAR(120) NOT NULL
- `status` ENUM(`ACTIVE`,`INACTIVE`) default `INACTIVE`
- `notes` TEXT NULL
- `created_by` BIGINT NULL FK -> `users.id` NULL ON DELETE
- `updated_by` BIGINT NULL FK -> `users.id` NULL ON DELETE
- timestamps

## Constraints / indexes
- UNIQUE (`college_program_offering_id`, `code`)
- INDEX (`college_program_offering_id`, `status`)

## Ownership / scope
College ownership is inherited through:
`batches.college_program_offering_id -> college_program_offerings.college_id`.

Do not add independent Program/Curriculum/Session fields to Batch; those authoritative references already exist on Program Offering.

## Downstream
Future:
- `sections.batch_id -> batches.id`
- `student_enrollments.batch_id -> batches.id`

Downstream operational records must use the Batch rather than bypassing it.
