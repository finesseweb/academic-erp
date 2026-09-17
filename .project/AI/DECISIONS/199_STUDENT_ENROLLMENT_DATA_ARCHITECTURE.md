# ADR 199 — Student Enrollment Data Architecture (ENR-0)

## Status
IMPLEMENTED — FOUNDATION ONLY / OWNER QA REQUIRED

## Decision
Student Enrollment is implemented as a controlled lifecycle boundary, not as an extension of the dynamic Admission form and not as a boolean on an Applicant.

The authoritative chain is:

`Applicant/User -> Application -> Confirmed Admission -> Fee Clearance -> Student -> Student Enrollment -> Batch/Section/Later Lifecycle`

A Student is the stable College-owned person record. `student_enrollments` stores session/programme-offering academic membership. Programme/session placement must not be flattened into the Student master.

## Canonical Student core
ENR-0 establishes `students` with stable core data only: College, optional existing User identity, Admission/Application provenance, source type, future `student_uid`, full name, DOB, email, phone and lifecycle status. ENR-3 owns final Student UID/Roll-number generation rules; ENR-0 does not invent them.

Applicant-originated enrollment must reuse the same `users` login. ENR-2 will create Student + Enrollment and call `ApplicantStudentPromotionService::enableStudentAccess()` in one transaction. Imported legacy students may have no User initially; import must not create fake login accounts.

## Dynamic Application -> Student mapping
Admission form fields remain dynamic. They do not add columns to `students`.

Each dynamic field now has:
- `student_data_policy = APPLICATION_ONLY|STUDENT_PROFILE`
- optional `student_profile_key`

`APPLICATION_ONLY` remains in historical Application data and is not copied into Student Profile.
`STUDENT_PROFILE` may be copied by ENR-2 into `student_profile_values`, preserving the label/value/file snapshot and source field reference.

Core Applicant identity (Name/DOB/Email/Mobile) continues to come from ADR 034 identity/application data and must not be duplicated as arbitrary dynamic profile fields.

## Dynamic-field deletion/history rule
Unused Draft form fields may be hard-deleted under the existing Form Builder dependency checks. Once a field has application data/dependencies, historical data must be preserved. Future forms should retire/deactivate such fields rather than destroy historical answers. Student profile snapshots remain valid even if the source field is later retired; source FK uses SET NULL for physical cleanup safety.

## Direct College/University migration
ENR-4 will provide controlled CSV migration into the same `students` + `student_enrollments` architecture. There will be no separate imported-student master. Import source is represented by `source_type=IMPORT`.

Required ENR-4 flow: downloadable canonical CSV template -> upload -> column mapping -> validation -> preview/error report -> explicit confirm -> transactional import. Direct raw CSV-to-table insertion is prohibited.

Existing active-student migration must not fabricate Fee Clearance or Admission history. It establishes Student/Enrollment provenance as IMPORT under an explicit migration policy.

## Enrollment uniqueness and provenance
- One Admission may create at most one Student and one Enrollment.
- One Student may have at most one Enrollment for the same College Program Offering.
- Offering is session-bound and therefore carries the academic-session context.
- Batch/Section are nullable at enrollment foundation because assignment may occur when operationally applicable.
- Source provenance is explicit (`ADMISSION|IMPORT`).

## ENR roadmap
- ENR-0 — Student Data Architecture (this ADR)
- ENR-1 — Enrollment Eligibility Queue
- ENR-2 — Admission -> Student Enrollment transaction
- ENR-3 — Student identity / institutional and roll-number rules
- ENR-4 — CSV Student Import / Migration

After ENR-4 QA closure, resume the authoritative University Administration roadmap at the next Student Lifecycle dependency; do not create a parallel roadmap.
