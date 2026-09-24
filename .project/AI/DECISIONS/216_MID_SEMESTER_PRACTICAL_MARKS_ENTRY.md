# ADR 216 — Mid Semester, Practical and Marks Entry

Status: IMPLEMENTED — OWNER QA REQUIRED

Mid Semester and Practical reuse the ADR 215 Course Offering component/activity architecture. Each requires its matching ACTIVE component, an ACTIVE same-Course Faculty Allocation, duration, and an opening/closing window inside the exact Curriculum Term's active Academic Calendar period. Both follow DRAFT → PUBLISHED → CLOSED and snapshot the canonical Enrollment roster transactionally on publication.

Marks Entry consumes only `internal_assessment_activity_students`; it never rebuilds the roster. Every published student must be submitted exactly once as ENTERED or ABSENT. ENTERED requires marks between zero and the parent component maximum; ABSENT stores null marks. Re-entry increments a revision number and writes an audit event. Marks approval and finalization remain subsequent milestones and will lock/consume these entered records rather than creating a parallel marks store.
