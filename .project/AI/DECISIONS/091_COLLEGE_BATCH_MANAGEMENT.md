# ADR 091 — College Batch Management

Status: IMPLEMENTED — OWNER QA REQUIRED
Date: 2026-09-04

## Context
The frozen College Academic Setup order is:
`Program Offerings -> Intake / Seat Capacity -> Reservation / Quota -> Batches -> Sections -> College Academic Calendar`.

Admission Confirmation is now Owner-QA accepted. Before Student Enrollment can assign confirmed candidates into the operational academic structure, Batch and Section layers must exist.

## Decision
Introduce canonical `batches` as a child of one exact `college_program_offerings` record.

Batch inherits College, Program, Curriculum and Academic Session from Program Offering. It must not duplicate those academic master references and must not become another seat-capacity or reservation authority.

New Batch records start INACTIVE. Activation requires ACTIVE College + ACTIVE Program Offering + ACTIVE Intake. Reservation is not made mandatory because reservation is optional per effective seat bucket.

## Lifecycle
`INACTIVE -> ACTIVE -> INACTIVE`.

Changing the parent Program Offering is blocked once Batch is ACTIVE or has downstream Section/Student references. Future active Sections/Student Enrollments block Batch deactivation.

## Security
Permissions:
- `college_batch.view`
- `college_batch.create`
- `college_batch.update`
- `college_batch.enable`
- `college_batch.disable`

All are exact-College scoped and College-delegable. Enable/disable are sensitive.

## Capacity rule
Batch create/update/activation never changes Intake capacity, Reservation plans, Seat Allocations or Admissions. Those upstream decisions remain authoritative.

## Next
Owner QA -> Section Management -> College Academic Calendar -> Student Enrollment / Lifecycle.
