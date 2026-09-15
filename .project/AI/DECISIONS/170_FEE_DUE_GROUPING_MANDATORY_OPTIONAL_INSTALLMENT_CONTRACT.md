# ADR 170 — Fee Due Grouping, Mandatory/Optional and Installment Payable Contract

## Decision
Fee liabilities remain atomic at Fee Demand Item / Installment Schedule level. The collection-facing projection groups open liabilities by authoritative due date so multiple Fee Heads due on the same date can be paid in one transaction without merging accounting records.

## Authoritative due source
1. If a Fee Demand Item has ACTIVE installment schedules, each installment due date is authoritative for that scheduled principal. The parent item due date is not double-counted.
2. If there is no ACTIVE installment schedule, the Fee Demand Item snapshotted due date is authoritative.
3. Approved Student Benefits reduce the non-installment net amount. Installment schedules already carry the post-benefit distribution/recalculation contract from ADR 143.
4. Paid installment amount reduces that installment's open amount.
5. Future Payment Collection allocations must reduce the same atomic source row and must never rewrite Gross Fee Demand or Gross Fee Demand Item.

## Same-date grouping
Rows sharing a due date are presented as one Due Group. Example: Tuition 20,000 + Library 1,000 + Lab 2,000, all due 15-Jul, may be collected in one transaction for 23,000 while allocation remains head/item/schedule-wise.

## Mandatory vs non-mandatory
`is_mandatory` is a Fee Demand Item snapshot and must not be recomputed from later Fee Setup edits.
- Mandatory rows contribute to `mandatory_due`.
- Non-mandatory rows contribute to `optional_due` and are not silently promoted to mandatory merely because they share a due date.
- A collection transaction may include both, but allocation records remain separate.
- Payment Collection must explicitly define selection/acceptance behavior for non-mandatory liabilities before it can post them; this ADR does not invent an acceptance state in existing historical data.

## Installments across several heads
If Tuition, Library and Lab each have installments on 15-Jul, those installment rows form the 15-Jul Due Group. If their second installments are all on 15-Aug, they form a separate 15-Aug Due Group. Different installment dates never get grouped merely because the parent Fee Heads belong to the same billing period.

## Late Fine linkage
Late Fine remains an auditable separate charge. For an item under installments, the installment due date is the penalty clock. A parent item due date must not also generate a duplicate fine. Non-installment penalty posting must use the snapshotted Fee Demand Item due date once collection/allocation can authoritatively determine its remaining paid/open principal. Until that allocation source exists, do not fake paid state.

## Implementation
`FeeDueGroupingService` is the canonical server projection and Fee Demand response now exposes `due_groups`. Payment Collection + Allocation must consume this contract rather than reimplement grouping in the UI.

## QA gate before Payment Collection
Verify same-date heads group correctly; mandatory/optional subtotals remain distinct; benefit-adjusted net is used; ACTIVE installments replace parent due source; paid installment balance is respected; different due dates form different groups; gross demand/item values remain unchanged.
