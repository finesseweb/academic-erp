# Student Enrollment Phase — ENR-0 to ENR-4

## Scope
This phase is a controlled branch inside the existing University Administration roadmap.

1. ENR-0 Student Data Architecture — schema/data contract and provenance.
2. ENR-1 Enrollment Eligibility Queue — Session -> Programme Offering -> eligible/blocked/enrolled candidates; Fee Clearance is consumed, never recalculated.
3. ENR-2 Admission -> Student Enrollment — backend revalidation, Student/Enrollment creation, Applicant login promotion, audit/toast/loading.
4. ENR-3 Student Identity — Student UID and institutional/class/exam roll ownership/generation after exact existing schema/rule reconciliation.
5. ENR-4 CSV Import/Migration — template, mapping, validation, preview/errors and controlled import into the same Student/Enrollment architecture.

## Shared UI rules
All UI milestones consume the existing shared Loader, Toast, App Dialog, semantic Lucide icon and server-side high-volume filtering standards. No page-local replacement infrastructure is allowed.

## Closure
Each ENR milestone requires Owner QA before the next ENR is eligible. After ENR-4 closure, return to the authoritative architecture roadmap.

## 2026-09-17 checkpoint
ENR-3 / ADR 203: OWNER QA PASS / CLOSED. ENR-4 / ADR 204: IMPLEMENTED / OWNER QA REQUIRED. ENR-4 converges legacy CSV students into the same Student + Enrollment architecture and introduces no parallel Student domain.

## ENR-5 — Canonical academic context prerequisite
Before Student Profile and downstream academic modules, legacy ADMISSION enrollments must converge to the same canonical Enrollment academic structure already produced by current Admission enrollment and Student Import. `source_type` is provenance only. UI/business modules must read Curriculum/Discipline/Specialization from `student_enrollments` and resolved subjects from `student_enrollment_course_choices`.

## ENR-5 closure — 2026-09-18
OWNER QA PASSED / CLOSED. Legacy normalization, idempotency, and fresh Admission-route canonical persistence were verified. ENR-6 Student Profile may proceed against canonical Student + Enrollment context.
