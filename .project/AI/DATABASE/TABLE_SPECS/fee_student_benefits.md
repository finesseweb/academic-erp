# Table: `fee_student_benefits`

## Purpose
Stores data related to the fee student benefits domain in Academic ERP.

## Database Table
`fee_student_benefits`

## Columns

- `university_id` (unsignedBigInteger)
- `college_id` (unsignedBigInteger)
- `admission_id` (unsignedBigInteger)
- `fee_demand_id` (unsignedBigInteger)
- `fee_scholarship_scheme_id` (unsignedBigInteger)
- `application_mode` (enum)
- `status` (enum)
- `eligible_base_amount` (decimal)
- `calculated_benefit_amount` (decimal)
- `sanctioned_amount` (decimal)
- `eligibility_snapshot` (json)
- `scheme_name_snapshot` (string)
- `scheme_code_snapshot` (string)
- `benefit_type_snapshot` (enum)
- `calculation_type_snapshot` (enum)
- `benefit_value_snapshot` (decimal)
- `maximum_benefit_amount_snapshot` (decimal)
- `application_note` (text)
- `decision_note` (text)
- `applied_at` (timestamp)
- `applied_by` (unsignedBigInteger)
- `decided_at` (timestamp)
- `decided_by` (unsignedBigInteger)
- `cancelled_at` (timestamp)
- `cancelled_by` (unsignedBigInteger)
- `cancellation_reason` (text)
- `university_id` (foreign)
- `college_id` (foreign)
- `admission_id` (foreign)
- `fee_demand_id` (foreign)
- `fee_scholarship_scheme_id` (foreign)
- `applied_by` (foreign)
- `decided_by` (foreign)
- `cancelled_by` (foreign)
- `fee_student_benefit_id` (unsignedBigInteger)
- `fee_demand_item_id` (unsignedBigInteger)
- `fee_head_id` (unsignedBigInteger)
- `eligible_amount` (decimal)
- `calculated_amount` (decimal)
- `fee_student_benefit_id` (foreign)
- `fee_demand_item_id` (foreign)
- `fee_head_id` (foreign)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.

## Current Finance Link - 2026-09-12

`fee_demand_id` anchors the Benefit to an immutable academic billing-period
liability. Approval changes adjusted/outstanding payable values but does not
change the Demand's billing period, source due dates, or Calendar Period.
