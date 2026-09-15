# ADR 066 — Admission Application Scoped Numbering and Internal Capture Parity

Date: 2026-09-01
Status: Accepted

## Context

Two Stage-1 QA inconsistencies were visible:
1. A Full Test Data Reset removed application rows but the next visible Application Number continued from the global database ID (for example `000004`).
2. A later Candidate Eligibility UI patch accidentally regressed the internal `Add Application` dialog to the older Seat Bucket Choices UI, while the public Applicant Portal already used curriculum-facing academic preference capture.

## Decision

### Application numbering
Use a dedicated `college_admission_application_sequences` row per College + Admission Cycle. Allocation is performed inside the application creation transaction with row locking. Existing data is respected by bootstrapping a missing sequence from the highest visible suffix already issued in that scope.

Full Academic/Test Data Reset removes sequence rows for the reset University's Colleges. Selective/individual cleanup never rewinds a sequence.

### Internal application capture
Internal `Add Application` and public Applicant Portal share the same application data model and academic-selection semantics. Application capture must not expose seat buckets. Regular Submit resolves the processing bridge from academic preference and locks the ACTIVE Selection Rule. Direct Admission bypasses the selection-rule chain and continues through its authorized downstream route.

## Consequences

- Full QA reset produces `...-000001` again for a cleared College + Admission Cycle.
- No global AUTO_INCREMENT manipulation is required, avoiding cross-University/College side effects.
- Internal and public application capture cannot drift between Seat Bucket UI and curriculum-facing academic choices.
- Seat capacity/reservation/allocation stay downstream as previously frozen.
