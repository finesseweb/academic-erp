# ADR 202 — Dynamic Application Field → Student Profile Mapping UI

**Status:** IMPLEMENTED / OWNER QA PENDING  
**Date:** 2026-09-16  
**Branch:** ENR-2 corrective completion (ENR2-11)

## Decision
Expose the ENR-0 `college_admission_form_fields.student_data_policy` policy in both University base-field and College custom-field builders as **Data Usage**:

- `APPLICATION_ONLY` → **Application Only** (explicit choice for admission-only data)
- `STUDENT_PROFILE` → **Student Profile**

`student_profile_key` remains an implementation key, not a second administrator decision. When a field is marked Student Profile, the backend uses its stable `field_key` as `student_profile_key` (or preserves an existing profile key on later edits). Returning a field to Application Only clears `student_profile_key`.

## Safety / historical behavior
- Existing fields remain `APPLICATION_ONLY` because ENR-0 already defaulted the DB column to that value. Newly created fields default to `STUDENT_PROFILE` in the Form Builder UI; this changes no historical row automatically.
- Changing Data Usage affects only **future Student creation/enrollment**. It does not backfill, delete, or rewrite existing `student_profile_values`.
- Application answers remain authoritative historical application data regardless of policy.
- No new table, column, FK, permission, seed/reference data, or migration is introduced by ADR 202.
- Existing System Use, validation, visibility, academic applicability, Application Entry, Admission and Fee workflows are unchanged.

## Enrollment consumer
ADR 201 `StudentEnrollmentService` remains the only ENR-2 consumer. At Student creation it copies only submitted values whose field is `STUDENT_PROFILE` and has a profile key. `APPLICATION_ONLY` values are not copied.

## UI scope
- University Admission Form Setup: Add/Edit University Base Field.
- College Admission Form Setup: Add/Edit College-owned dynamic field.
- Inherited University fields remain governed by existing ownership/locking rules; this ADR does not grant College edit rights over University-owned structure.

## QA gate
ENR2-11 must verify one Student Profile field and one Application Only field on a newly enrolled test student. Existing Student profile rows must remain unchanged when a field policy is later edited.
