# ADR 212 — Attendance Operations Foundation

Status: IMPLEMENTED — OWNER QA REQUIRED

Attendance is recorded only against a dated, non-cancelled `ClassSchedule`. The register derives Course Offering, Batch/Section delivery scope and Faculty through the existing scheduling chain, and derives students exclusively from canonical `StudentEnrollment` plus the exact `student_enrollment_course_choices` mapping. Admission/Import provenance is not consulted.

Class Schedule now enforces the effective College Academic Calendar period and Holiday/Vacation rules on create/edit. Attendance does not duplicate calendar resolution; it inherits the governed date by continuing to require a legitimate Class Schedule.

The applicable ACTIVE + APPROVED Academic Policy is resolved with existing precedence (Curriculum → Program Template → Degree Level → University). A configured Attendance Rule is mandatory. The register stores the resolved `academic_policy_id` for historical traceability but does not duplicate thresholds.

Registers support `DRAFT → FINALIZED`. Finalization locks raw records and marks a still-SCHEDULED Class Schedule COMPLETED in the same transaction. Authorized correction reopens the register, increments `revision_no`, requires a reason and writes an audit event. It never silently rewrites policy or finance data.

Statuses are PRESENT, ABSENT, LATE and EXCUSED. PRESENT/LATE/EXCUSED count as attended. Percentage display applies the policy rounding rule. The resolved Attendance Rule `calculation_level` is operational: `COURSE` aggregates finalized records for the exact Course Offering, `TERM` aggregates finalized records across the same Programme Offering and exact Curriculum Term, and `OVERALL` aggregates finalized records across the same Programme Offering. The aggregation always remains student-specific and uses only finalized raw Attendance records. Condonation, special exemption and final examination eligibility remain later controlled milestones; they must consume finalized raw attendance and never mutate it.

Fee boundary: Attendance never changes Fee Demands, payments, balances or clearance. Future later-period Fee Demand eligibility consumes authoritative Academic Progression output, not raw attendance.

## 2026-09-22 QA corrective note — Enrollment placement prerequisite
Owner QA found a valid empty-roster case where canonical enrollments had no Batch / Section placement. Attendance must not compensate by widening a Section-scoped class to every student in the Programme Offering. The corrective implementation assigns `student_enrollments.batch_id` and `section_id` through the canonical Student Identity bulk-placement workflow with same-Offering / parent-child validation and audit. Attendance roster resolution remains strict and unchanged.
