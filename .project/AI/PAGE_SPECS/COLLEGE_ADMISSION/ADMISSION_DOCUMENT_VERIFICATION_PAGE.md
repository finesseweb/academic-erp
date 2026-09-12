# College Admission Document Verification Page

## Purpose
Provide the mandatory College-controlled verification gate between generated Merit / Roster and Seat Allocation.

## Route
`GET /college/{college}/admission-document-verification`

## Permissions
- view: `college_admission_document_verification.view`
- document review: `college_admission_document_verification.review`
- overall finalize: `college_admission_document_verification.finalize`

## Cohort
Only SUBMITTED applications appearing in a generated Merit roster for the selected Selection Rule are listed, in merit-rank order. The page does not recalculate merit or alter the locked Selection Rule.

## Document checklist
Uploaded dynamic Admission Form fields with type `FILE` or `IMAGE` are shown as documents. Staff can securely download each private file and mark it VERIFIED, REJECTED, or WAIVED. REJECTED/WAIVED requires remarks.

## Overall verification
- PENDING: not yet finalized or reset because an item changed.
- VERIFIED: all uploaded documents are VERIFIED/WAIVED; Seat Allocation may proceed.
- DEFICIENT: documents are incomplete/incorrect; notes are mandatory and Seat Allocation remains blocked.
- An application with no uploaded document fields still requires explicit authorized finalization.

## Downstream contract
Seat Allocation must require `VERIFIED` and persist the exact verification record ID. UI disabling is informational; backend enforcement is authoritative.
