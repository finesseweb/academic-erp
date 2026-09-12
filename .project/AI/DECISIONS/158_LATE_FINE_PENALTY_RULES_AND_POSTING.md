# ADR 158 — Late Fine / Penalty Rules and Posting

**Date:** 2026-09-09  
**Status:** Implemented — QA Pending

## Context
Installment Scheduling (ADR 142/143) creates authoritative due dates for installment-enabled Fee Demand Items. The next Fee Phase milestone is Late Fine / Penalty. The project financial invariant remains unchanged: Gross Fee Demand and Gross Fee Demand Item are immutable; downstream financial effects must be separate, auditable records.

## Decision
Late Fine is implemented as a separate policy + posted-charge subsystem against **ACTIVE installment schedules**.

### Rule scope
A College Late Fine Rule is scoped to:
- College
- Academic Session (derived from Program Offering)
- exact ACTIVE College Program Offering
- exact ACTIVE Fee Head
- source type `INSTALLMENT`

Only one ACTIVE rule may exist for the same College Program Offering + Fee Head + installment source. New rules start `INACTIVE`; an ACTIVE rule must be deactivated before editing. Once a Rule has any calculation history, the Rule becomes immutable and a new Rule version must be created instead of rewriting historical policy.

### Calculation modes
Supported calculation types:
- `FIXED`
- `PERCENTAGE` of the installment's current unpaid amount

Supported frequencies:
- `ONE_TIME`
- `PER_DAY`
- `PER_WEEK`

Additional policy fields:
- Grace Days
- optional Maximum Fine cap
- Notes

For percentage rules, the configured percentage cannot exceed 100% per calculation unit.

### Calculation base and timing
For an ACTIVE installment:

`Base Unpaid = Installment Amount - Installment Paid Amount`

Late Fine starts only after:

`Installment Due Date + Grace Days`

If the installment is not overdue, is fully paid, is CANCELLED, or its parent Fee Demand is CANCELLED, no active fine is posted.

`PER_WEEK` uses `ceil(overdue_days / 7)`.

### Financial invariant
Late Fine does **not** modify:
- Fee Demand `total_amount`
- Fee Demand Item `amount`
- approved Student Benefit amounts
- installment principal amounts

Instead, the active late fine is a separate charge. Operational payable becomes:

`Payable incl. Fine = Fee Demand Outstanding + ACTIVE Late Fine Charges`

The Fee Demand register now shows Late Fine and Payable incl. Fine separately.

### Revision / recalculation history
Late Fine recalculation never overwrites historical fine rows.
- a changed active calculation creates a new ACTIVE charge
- prior active charge becomes `SUPERSEDED`
- if the overdue unpaid base disappears, the prior active charge becomes `REVERSED`
- audit events record POSTED / RECALCULATED / REVERSED transitions

This preserves posted-finance history while keeping exactly the current active charge visible operationally.

### Execution
Two execution paths exist:
1. College UI: `Calculate / Recalculate` with an explicit As Of date for controlled QA/operations.
2. Scheduled command: `fees:recalculate-late-fines`, registered daily at 00:10. Production hosting must run Laravel `schedule:run` through cron for scheduled execution.

The manual UI path remains authoritative for QA and controlled recalculation.

### Cancellation / replacement integration
When an unpaid Fee Demand is cancelled, or Admission Confirmation revocation cancels its Fee Demands, ACTIVE Late Fine charges are marked `REVERSED` and ACTIVE installment schedules are cancelled. Historical fine rows remain.

When an installment schedule is replaced before collection, ACTIVE fine charges linked to the replaced installment rows are marked `REVERSED` before the old schedule rows are cancelled.

### Register scalability / consistency
Late Fine Register follows the common high-volume student-register rule:
- current Academic Session selected by default
- Session may be changed for historical review
- Programme Offering filter is optional
- search is server-side
- server-side pagination 25 / 50 / 100
- compact table rather than giant cards

Batch is not used as a universal filter because this financial workflow can exist before Student Enrollment / Batch Assignment.

### Permissions
- `college_fee_late_fine.view`
- `college_fee_late_fine.manage`
- `college_fee_late_fine.calculate`

### Test Data Cleanup
`Late Fine Charges` is added to Test Data Cleanup. Cleanup removes the selected active charge together with its superseded/reversed revision lineage for the same Rule + Installment Schedule. Late Fine Rule master configuration is preserved by default, consistent with the standing rule that transactional QA data is cleaned while real/master configuration is retained unless explicitly test-only.

Fee Demand cleanup also treats Late Fine Charges as downstream financial references.

## Deliberate scope boundary
This ADR uses installment due dates because those are the currently implemented authoritative fee due dates. Non-installment Demand-Item due-date policy is not invented here. If the project later introduces an authoritative standalone demand-item due date, this same charge engine can be extended with another source type without rewriting installment history.

No Student Enrollment / Batch dependency is introduced.

## QA Gate
Before Late Fine is marked PASS:
1. Create a Late Fine Rule for an installment-enabled Fee Head.
2. Confirm new rule is INACTIVE and ACTIVE rule cannot be edited.
3. Activate it; confirm duplicate active rule for same Offering + Fee Head is blocked.
4. Use an installment due date in the past and calculate as of a later date.
5. Verify fixed ONE_TIME calculation.
6. Verify grace period.
7. Verify PER_DAY and PER_WEEK calculation.
8. Verify PERCENTAGE calculation uses current unpaid installment amount.
9. Verify Maximum Fine cap.
10. Recalculate on a later date; verify old row becomes SUPERSEDED and new row becomes ACTIVE.
11. Confirm Gross Demand / Fee Demand Item / Student Benefit / installment principal do not change.
12. Confirm Fee Demand shows separate Late Fine and Payable incl. Fine.
13. Replace an unpaid installment schedule; prior ACTIVE fine must reverse.
14. Cancel an unpaid demand or revoke its Admission Confirmation; ACTIVE fine must reverse.
15. Test Data Cleanup must remove Late Fine test charge lineage.
16. Verify current-session default + historical Session filter + pagination.

## Next implementation after QA PASS
Payment Collection + Allocation. Payment allocation must treat installment principal and active Late Fine as separate financial components and must update installment `paid_amount` without rewriting posted history.
