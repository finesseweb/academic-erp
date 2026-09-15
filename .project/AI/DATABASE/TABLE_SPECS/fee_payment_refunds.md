# Table: `fee_payment_refunds`

## Purpose
Refund header against one existing Fee Payment receipt.

## Key columns
`university_id`, `college_id`, `admission_id`, `academic_session_id`, `fee_payment_id`, `refund_no`, `refund_date`, `amount`, `refund_mode`, `reference_no`, `reason`, `status`, `refunded_by`, timestamps.

## Contract
A POSTED refund is an outgoing financial event. Eligibility is enforced by child allocations against the original refundable Fee Demand Item snapshots.
