# ADR 107 — Fee charge policy is billing-period specific

## Status
Accepted — 2026-09-05

## Context
Fee configuration now follows a period-first UI: Fee Structure → Billing Period → Fee Item. The same Fee Head may appear in more than one billing period with different amounts. Keeping Mandatory, Enrollment Clearance Required, Installment Allowed, display order and charge status only on the shared Fee Structure Item made those rules unintentionally identical across every Semester/Academic Year.

## Decision
For recurring structures (`PER_TERM` and `PER_ACADEMIC_YEAR`), each configured billing-period charge has its own policy settings:

- amount
- mandatory / optional
- enrollment-clearance required
- installment allowed
- display order
- ACTIVE / INACTIVE status

`fee_structure_item_period_settings` stores the period-specific policy keyed by `(fee_structure_item_id, period_no)`. Existing item-level fields remain as compatibility/default fields and continue to be authoritative for `ONE_TIME` structures. Existing recurring period amounts are backfilled with the parent item's former shared settings during migration.

UI editing happens inside the selected billing period. Editing Semester 2 cannot silently change Semester 1 policy. A Fee Head absent from a billing period remains Not Applicable by presence/absence, per ADR 104.

## Consequences
- Semester 1 Tuition can be Mandatory + Enrollment Clearance Required + no installments while Semester 2 Tuition can be Mandatory + no enrollment clearance + installments allowed.
- Configured totals and activation use the effective period-specific status.
- Future Fee Demand must copy the effective period-specific policy from the exact billing-period charge, not blindly from the shared parent Fee Item.
- Test Data Cleanup removes period settings before Fee Structure Items.
