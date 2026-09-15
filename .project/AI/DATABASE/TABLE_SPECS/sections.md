# Table Spec — sections

## Purpose
Operational class/section grouping under one exact College Batch.

## Columns
- `id` BIGINT PK
- `batch_id` BIGINT NOT NULL -> `batches.id` RESTRICT
- `code` VARCHAR(50) NOT NULL
- `name` VARCHAR(120) NOT NULL
- `status` ENUM(`ACTIVE`,`INACTIVE`) default `INACTIVE`
- `notes` TEXT nullable
- `created_by` -> `users.id` nullable / NULL on delete
- `updated_by` -> `users.id` nullable / NULL on delete
- timestamps

## Constraints
- UNIQUE (`batch_id`, `code`)
- INDEX (`batch_id`, `status`)
- Section does not store Program/Curriculum/Session/Intake/Reservation capacity; those are inherited through Batch -> Program Offering.

## Downstream
- future `student_enrollments.section_id -> sections.id`
