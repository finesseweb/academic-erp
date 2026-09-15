# Table: `fee_adjustments`

## Purpose
Auditable manual liability corrections against one immutable Fee Demand Item.

## Key columns
`university_id`, `college_id`, `admission_id`, `academic_session_id`, `fee_demand_id`, `fee_demand_item_id`, `adjustment_no`, `adjustment_date`, `direction`, `amount`, `reason_code`, `reason`, `status`, `posted_by`, `reversed_at`, `reversed_by`, `reversal_reason`, timestamps.

## Contract
CREDIT lowers liability; DEBIT increases liability. POSTED rows affect Demand `adjusted_amount`; REVERSED rows remain historical but no longer affect current liability.
