# Table Spec — `college_admission_seat_allocations`

## Purpose
Immutable-scope, auditable physical seat decision produced from a generated Merit / Roster row before Admission Confirmation.

## Parent links
- `college_id -> colleges.id`
- `college_program_intake_id -> college_program_intakes.id`
- nullable `college_program_reservation_plan_id -> college_program_reservation_plans.id`
- `college_admission_merit_entry_id -> college_admission_merit_entries.id`
- `college_admission_application_id -> college_admission_applications.id`
- `college_admission_application_choice_id -> college_admission_application_choices.id`
- `college_admission_score_id -> college_admission_scores.id`
- `college_admission_selection_rule_id -> college_admission_selection_rules.id`
- nullable `physical_reservation_category_id -> reservation_categories.id`
- actor FKs -> `users.id`

## Important columns
- `bucket_type`, `bucket_key`: exact effective Intake seat bucket snapshot.
- `merit_rank`, `final_weighted_score`: immutable Merit decision snapshot.
- `physical_seat_type`: `OPEN|RESERVED`.
- physical category id/code/name: reserved physical category reference + display snapshot; null id for Open.
- `allocation_round`, `decision_note`.
- status: `ALLOCATED|CANCELLED`.
- allocated/cancelled actor/time/reason fields.

## Constraints / indexes
- one row per Merit entry.
- one row per Application Choice.
- bucket/status, application/status, category/status and rule/status indexes support capacity and workflow checks.
- Application-level one-active-seat rule is transactionally enforced because MySQL does not provide the required partial unique index for only ACTIVE rows.

## Child table — `college_admission_seat_allocation_horizontal_categories`
Stores zero or more Horizontal categories independently from physical seat consumption.

Important columns:
- `college_admission_seat_allocation_id`
- `reservation_category_id`
- category code/name snapshots
- `fulfills_target`
- `created_by`

Unique allocation + category prevents duplicate Horizontal recording. Child rows cascade only when disposable test cleanup removes their parent allocation; normal product workflow does not expose destructive deletion.

## Mandatory Document Verification reference — 2026-09-03
`college_admission_document_verification_id` is required and RESTRICT-links the exact `college_admission_document_verifications` row that was finalized VERIFIED before allocation. Backend allocation rejects missing/PENDING/DEFICIENT verification.
