# Changelog — ENR-5 Canonical Enrollment Academic Normalization — 2026-09-18

- Added shared `StudentEnrollmentAcademicContextService` as the canonical Enrollment academic-context writer.
- Admission enrollment and Student Import now persist academic context through the same service.
- Added dry-run-first legacy ADMISSION Enrollment normalization service/Artisan command.
- Normalization backfills only from the Enrollment's own authoritative Admission/Application academic preference and saved course choices.
- Conflicts/missing provenance are reported and never guessed or silently overwritten.
- Added audit event `student.enrollment.academic_normalized` for applied normalization.
- No schema migration; dedicated DB-impact, relationship, schema-ownership and ADR documentation updated.
- Owner QA required before ENR-5 closure.
