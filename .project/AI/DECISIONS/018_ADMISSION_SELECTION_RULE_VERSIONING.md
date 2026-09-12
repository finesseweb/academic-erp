# ADR 018 — Admission Merit / Roster / Selection Rule Versioning

Status: IMPLEMENTED — OWNER QA REQUIRED
Date: 2026-08-26

## Decision
Merit / Roster / Selection Rules attach to the exact effective Intake admission seat bucket, not mandatorily to a Reservation Plan.

Operational chain:

College
→ Program Offering
→ Active Intake
→ Effective Admission Seat Bucket
→ optional Reservation / Seat Distribution
→ Merit / Roster / Selection Rule Version
→ future Student Admission / Seat Consumption

## Reservation Optionality Rule
Reservation / Seat Distribution is optional per effective Intake seat bucket.

- If no Reservation Plan is defined for a bucket, the complete bucket capacity is treated as Open/General and Selection Rules may be configured.
- If a Reservation Plan is defined for a bucket, that plan must be `ACTIVE` before Selection Rules for that bucket can be configured or activated.
- An `INACTIVE` Reservation Plan blocks only its own seat bucket. It must never block unrelated General/Specialization buckets.
- When an ACTIVE Reservation Plan exists, the Selection Rule stores its identifier for traceability.
- When no Reservation Plan exists, `college_program_reservation_plan_id` remains null and the rule preserves Intake + bucket identity directly.

## Versioning Rule
- A newly created rule is `INACTIVE`.
- Only `INACTIVE` versions are editable.
- Only one version may be `ACTIVE` per Intake seat bucket at a time.
- Activating a newer version automatically marks an older active version for the same bucket `RETIRED`.
- `ACTIVE` and `RETIRED` versions are immutable.
- Future Student Admission must reference the exact rule version used.

## Activation Gate
A rule can activate only when Program Offering and Intake remain ACTIVE. If Reservation is defined for that bucket, the Reservation Plan must also be ACTIVE.

## Lifecycle Dependency
An ACTIVE Reservation Plan used by an ACTIVE Selection Rule cannot be deactivated until the Selection Rule is retired. If an ACTIVE Selection Rule currently treats a bucket as Open/General, a newly created Reservation Plan for that bucket cannot be activated until that Selection Rule is retired and a new rule version is created under the Reservation context.

## Structured Ranking Detail
ADR 019 extends this decision: executable tie-break logic is stored as ordered child records and qualifying thresholds are mode-aware normalized 0-100 fields. Free-text tie-break wording is policy documentation only.
