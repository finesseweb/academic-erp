# ENR-3 — Student Identity

Route: `/college/{college}/student-identities`
Sidebar: Student Management > Student Identity
ADR: 203
Status: IMPLEMENTED — OWNER QA REQUIRED

Purpose: configure and assign Student UID, University Roll No. and enrollment-specific Class Roll No. for enrolled students. Exam Roll is explicitly excluded.

Student Identity is also the canonical placement UI. Authorized users may select multiple enrollments from one Programme Offering and atomically assign them to an ACTIVE Batch and its ACTIVE child Section. Mixed-offering selections, cross-College rows and invalid parent/child combinations are rejected. Placement writes the existing Enrollment FKs and emits one audit event per changed enrollment; it does not alter identity numbers, Admission, Intake or Fee Demand.

The page uses Session -> Programme Offering dependent filters, server-side search/pagination, shared loading infrastructure, theme-native controls/icons, shared App Dialog confirmation, button processing states and Toast feedback.

Identity formats are College-specific. Saving rules never changes already assigned identifiers. Existing ENR-2 students with NULL identifiers show Pending and may be assigned. New enrollments are assigned through the same transactional identity service.

QA: migration; permission; sidebar/page scope; default settings; format save; existing student assignment; no replacement on repeated assignment; new enrollment auto-assignment; College uniqueness; Programme Offering class-roll scope; concurrent sequence safety; bulk placement single-offering guard; Batch/Section parent validation; atomic rollback; loader/dialog/toast; cross-college denial; Exam Roll absent; Admission/Fee/Enrollment regression.

## ENR-3 QA refinement — Discipline visibility and dependent filter (2026-09-17)
- Enrolled Students table displays Discipline as a separate academic-context column beside Programme.
- Discipline is sourced from the authoritative admitted Application Academic Preference; no duplicate discipline field is added to Student or Student Enrollment.
- Filters are contextual/dependent: Session -> Programme Offering -> Discipline.
- Changing Session resets Programme Offering and Discipline; changing Programme Offering resets Discipline.
- Discipline options are limited to disciplines represented by enrolled students within the selected Session/Programme Offering context.
- Filtering remains server-side and College-scoped for large datasets.
- No database migration or new domain column/table is required for this refinement.

### ENR-3.3 Class Roll Scope
Identity Numbering Rules includes `Class Roll Scope` with `Programme Offering` and `Discipline` options. Programme Offering remains the default. Discipline scope restarts the sequence independently for each authoritative discipline within an offering. Saving a changed scope never modifies an already assigned Class Roll.

## RBAC / Audit closure (ENR-3.6)
- Page/list access: `college_student_identity.view`.
- Save Identity Rules + Assign Identity: `college_student_identity.manage`.
- Sidebar child visibility: Identity View; mutation controls remain gated by Manage.
- RBAC grouping: `Student Management` → Student Enrollment (View, Enroll) + Student Identity (View, Manage).
- Audit: identity assignment and identity-rule changes must create College-scoped audit rows with actor/IP and before/after snapshots.
