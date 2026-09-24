# ENR-3 Test Data Cleanup — Student Identity

## Owner QA correction
The System Maintenance → Test Data Cleanup UI must expose a **Student Management → Students** cleanup group. ENR-3 identity test data must not require manual SQL for ordinary QA cleanup.

## Student cleanup behavior
Cleaning one test Student deletes only Student-owned ENR data:
- `student_profile_values` for that Student
- `student_enrollments` for that Student (including `class_roll_no`)
- the `students` row (including `student_uid` and `university_roll_no`)

If the Student originated from a self-registered Applicant, cleanup restores the linked `applicant_profiles` row to `APPLICANT`, clears `student_id` / `student_enabled_at`, and restores the same user account from `STUDENT` to `APPLICANT`.

The source Application, Admission, Fee Demand, Payment, Adjustment, Refund and Fee Clearance evidence are not deleted by Student cleanup. This allows Enrollment/Identity QA to be repeated without rebuilding the full admission lifecycle.

## Identity sequence safety
Individual Student cleanup **never rewinds** `student_identity_sequences`. A consumed visible identity is not reused. This is production-safe behavior.

The full disposable Academic Test Reset may remove ENR-3 settings/sequences only after Student/Enrollment test records are removed child-first, as documented by ADR 203.

## Identity-only cleanup (ENR-3.4)
Use **System Maintenance -> Test Data Cleanup -> Student Management -> Student Identity Assignments** when QA needs to retest Assign without deleting/re-enrolling the Student.

Expected result:
- Student UID -> NULL
- University Roll No. -> NULL
- Class Roll No. -> NULL
- Student remains
- Student Enrollment remains
- Student Profile/Application/Admission/Fee data remain
- identity sequence is NOT rewound; the next Assign consumes the next number.

Enrollment itself no longer assigns identity. After a Student cleanup + re-enrollment, Student Identity must show PENDING until Assign is explicitly confirmed.

## ENR-3.5 — Empty-scope sequence reset for disposable Student cleanup (2026-09-17)
Targeted **Student Management -> Students** cleanup is allowed to reset ENR-3 sequence state only when the cleanup makes the relevant identity scope completely empty.

Rules:
- Student UID sequence (`STUDENT_UID / COLLEGE`) is deleted only when the College has no retained Student with a Student UID.
- University Roll sequence (`UNIVERSITY_ROLL / COLLEGE`) is deleted only when the College has no retained Student with a University Roll.
- Class Roll sequence is evaluated per captured `class_roll_scope_key`; the exact Programme Offering / Discipline scope is deleted only when no retained Enrollment in that scope has a Class Roll.
- Other Colleges, Programme Offerings, Disciplines and retained assigned identities are never reset.
- After an empty test scope is reset, the next explicit Student Identity Assign starts that scope at sequence `1` again.
- **Student Identity Assignments** (identity-only cleanup) still never resets sequences; its purpose is to retest issuance while preserving non-reuse semantics.
- This reset behavior belongs only to guarded Test Data Cleanup. Normal production cancellation/deletion/lifecycle operations must never rewind institutional identity sequences.

This is not Student-ID/auto-increment based numbering. `student_identity_sequences` remains the authoritative concurrency-safe allocator.
