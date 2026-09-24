# ADR 214 — Attendance Exceptions and Final Eligibility

Status: IMPLEMENTED — OWNER QA REQUIRED

## Decision

Implement Condonation and Medical/Special Exemption as typed, auditable requests separate from finalized raw attendance. Both use the Course Offering as a stable evaluation anchor while the resolved Academic Policy determines COURSE, TERM or OVERALL aggregation.

Final Examination Attendance Eligibility is a persisted snapshot, unique by Student Enrollment + Course Offering. Its basis is `NOT_REQUIRED`, `NORMAL`, `CONDONATION`, `SPECIAL_EXEMPTION`, or `SHORTAGE`. Examination must consume this final result when attendance is required; it must not independently reinterpret raw Attendance Records.

## Controls

- College ownership is checked through Enrollment and Course Offering → Batch → Programme Offering.
- Condonation limits and exception enablement are revalidated by Laravel.
- Only pending requests can be decided; decision remarks are mandatory.
- Approved exceptions do not modify held, attended, status or percentage.
- Request, decision and finalization events are audited.
- REST/Inertia is used; realtime transport is not justified.
