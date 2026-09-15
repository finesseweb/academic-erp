# ADR 089 — Admission Document Verification Gate

Status: IMPLEMENTED — OWNER QA REQUIRED  
Date: 2026-09-03

## Context
The main ERP hierarchy freezes Admission processing as `Merit / Entrance -> Reservation / Quota -> Document Verification -> Seat Allocation -> Admission Approval -> Student Enrollment`. Merit / Roster existed, but the explicit Document Verification stage had not yet been implemented. Seat Allocation must therefore not bypass this required gate.

## Decision
Implement College Admission Document Verification as an application-level workflow immediately after generated Merit / Roster and before physical Seat Allocation.

Only applications represented in a generated Merit roster are shown. Uploaded Admission Form `FILE` / `IMAGE` values become the document checklist. Every document can be `VERIFIED`, `REJECTED`, or `WAIVED`; rejection and waiver require remarks. Overall application verification is `PENDING`, `VERIFIED`, or `DEFICIENT`.

Overall `VERIFIED` is allowed only when every uploaded document is `VERIFIED` or formally `WAIVED`. A ranked application with no uploaded FILE/IMAGE values still requires an explicit authorized overall verification. Changing any item after finalization resets the overall status to `PENDING`.

## Seat Allocation gate
Seat Allocation must lock and require the exact application-level Document Verification row with status `VERIFIED`. The allocation persists `college_admission_document_verification_id`, making the upstream approval trace explicit and preventing later deletion while the seat decision depends on it.

## File access
Uploaded documents remain on the private `local` disk. Staff download is exposed only through an authenticated College-scoped route with `college_admission_document_verification.view` and an ownership check. Direct public storage URLs are not introduced.

## Permissions
- `college_admission_document_verification.view`
- `college_admission_document_verification.review`
- `college_admission_document_verification.finalize`

Review/finalize are sensitive College-delegable actions. Initial implementation grants them to `SUPER_ADMIN` and `COLLEGE_ADMIN` consistently with the Admission-processing baseline.

## Audit events
- `COLLEGE_ADMISSION_DOCUMENT_REVIEWED`
- `COLLEGE_ADMISSION_DOCUMENT_VERIFICATION_FINALIZED`
- maintenance-only: `TEST_COLLEGE_ADMISSION_DOCUMENT_VERIFICATION_CLEANED`

## Frozen linked flow
`Generated Merit / Roster -> Document Verification (VERIFIED) -> Seat Allocation / Consumption -> Admission Confirmation / Approval -> Student Enrollment`

College Academic Setup completeness remains separately mandatory: `Batches -> Sections -> College Academic Calendar` must not be skipped and will be linked when the downstream student/teaching stages require them.
