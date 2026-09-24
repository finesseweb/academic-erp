# 2026-09-18 — ENR-5 Closure + ENR-6 Student Profile

- ENR-5 / ADR 206 marked OWNER QA PASSED / CLOSED after legacy normalization, idempotency and fresh Admission-route canonical enrollment QA passed.
- Started ENR-6 / ADR 208 Student Profile View/Edit.
- Added Student Profile list/detail/edit, canonical enrollment/course context, RBAC and profile update audit.
- No Student-domain schema change; RBAC/reference migration only.

### ENR-6.1 QA correction
- Aligned Student Profile reservation-category options with the existing Admission Form authoritative resolver/validator: canonical `GENERAL` + ACTIVE VERTICAL University categories excluding general/open aliases.
- Replaced user-facing raw Curriculum ID with Curriculum name/code.
- No schema or stored student-profile value rewrite.
- ENR-6.2: moved current academic summary into Student Profile header; removed duplicate bottom enrollment presentation; category-labelled APPLICANT_CHOICE summary hides AUTO_MANDATORY/internal IDs.
- ENR-6.2: core DOB now uses shared DatePicker with explicit YYYY-MM-DD payload binding.
- ENR-6.2: governed Candidate Profile Photo now renders in the header and supports permission-gated, audited Student-owned replacement while preserving Admission provenance files.
- ENR-6.3 (2026-09-19): fixed Import-source Student Profile photo parity. Applicable Admission Form configuration now exposes the same empty photo slot/Add Photo action even when import created no file value; first upload creates the canonical governed profile-value row, later uploads update it. CSV import continues to exclude FILE/IMAGE fields.
