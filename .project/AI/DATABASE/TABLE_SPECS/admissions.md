# Table Spec — `admissions`

## Purpose
Canonical Admission Confirmation / Approval transaction created only after physical Seat Allocation has already been completed.

## Parent links
- `college_id -> colleges.id`
- `college_program_intake_id -> college_program_intakes.id`
- nullable `college_program_reservation_plan_id -> college_program_reservation_plans.id`
- nullable `curriculum_id -> curricula.id`
- `college_admission_application_id -> college_admission_applications.id`
- `college_admission_application_choice_id -> college_admission_application_choices.id`
- `college_admission_document_verification_id -> college_admission_document_verifications.id`
- `college_admission_seat_allocation_id -> college_admission_seat_allocations.id`
- nullable `college_admission_merit_entry_id -> college_admission_merit_entries.id`
- nullable `college_admission_score_id -> college_admission_scores.id`
- nullable `college_admission_selection_rule_id -> college_admission_selection_rules.id`
- confirm/revoke actor FKs -> `users.id`

All business-parent FKs use RESTRICT delete behavior.

## Important columns
- `admission_no`: stable, globally unique Admission Number.
- `status`: `CONFIRMED|REVOKED`.
- `decision_note`: optional approval/counselling/committee reference.
- `confirmed_at`, `confirmed_by`.
- `revoked_at`, `revoked_by`, `revocation_reason`.

## Constraints / indexes
- unique `admission_no`.
- one Admission history row per Application.
- one Admission history row per Seat Allocation.
- College/status, Intake/status and Selection Rule/status indexes support operational lists/reporting.

A revoked row is re-used if admission is later re-confirmed, preserving the same Admission Number and preventing parallel confirmation histories for one Application/Seat Allocation.

## Business rules
- Confirmation requires linked Seat Allocation status `ALLOCATED`.
- linked Document Verification must remain `VERIFIED`.
- linked Application must remain `SUBMITTED`.
- no capacity/reservation calculation is performed in this table/service.
- revocation is blocked once Student Enrollment consumes the Admission/Application.

For the current REGULAR Seat Allocation path these three references are populated. They remain nullable at schema level so the already-documented DIRECT admission path can later reach Admission/Enrollment without inventing fake Score/Merit/Selection Rule records.
