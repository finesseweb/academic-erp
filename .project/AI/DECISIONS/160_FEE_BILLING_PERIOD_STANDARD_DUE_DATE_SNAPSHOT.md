# ADR 160 — Fee Billing Period Standard Due Date + Demand Snapshot

Date: 2026-09-09
Status: IMPLEMENTED — QA PENDING

## Problem
Late Fine cannot be calculated correctly from a Billing Period label such as Semester 1 / Year 1 alone. The academic period tells us the academic coverage, but not when a specific Fee Head becomes payable. A fee can become due near the start of a period even though the academic period ends months later.

## Decision
Fee Setup now owns a **Standard Due Date** for each effective Fee Head / Billing Period charge.

Canonical scope:

`Fee Structure → Billing Period → Fee Head → Amount + Standard Due Date + charge policies`

The Standard Due Date is not inferred from the Billing Period end date and is not maintained by Late Fine.

### Recurring structures
For `PER_TERM` and `PER_ACADEMIC_YEAR`, Standard Due Date is stored in `fee_structure_item_period_settings.due_date`, so the same Fee Head may have a different due date in each billing period.

### One-time / specific-period structures
For `ONE_TIME`, `SPECIFIC_TERM`, and `SPECIFIC_ACADEMIC_YEAR`, Standard Due Date is stored on `fee_structure_items.due_date` because the Fee Structure itself already identifies the applicable billing coverage.

## Demand snapshot
When a Fee Demand is generated, the applicable Standard Due Date is copied to `fee_demand_items.due_date`.

This is a historical snapshot. Future Fee Setup changes must not silently change the due date on an already-generated Fee Demand Item.

## Installment interaction
- No installment schedule: `Fee Demand Item due_date` is the authoritative standard payment due date.
- Installment schedule exists: each ACTIVE installment schedule row has its own due date and controls installment payment timing.
- Creating installments does not mutate the Fee Demand Item Standard Due Date; it remains the original fee-policy snapshot.
- Gross Fee Demand and Gross Fee Demand Item amounts remain unchanged.

## Validation
- New/updated non-recurring Fee Items require Standard Due Date.
- New/updated ACTIVE recurring period settings require Standard Due Date.
- Fee Structure activation is blocked if any ACTIVE applicable Fee Head/Billing Period is missing Standard Due Date.
- Existing legacy active structures are not assigned an invented date by migration; they visibly remain `Not set` until explicitly remediated.
- New Fee Demand generation is blocked if an otherwise-effective Fee Head/Billing Period has no Standard Due Date, preventing new null-due financial snapshots.

## UI
- Fee Setup uses the project `DatePicker`; no external CSS or ad-hoc date control is introduced.
- Standard Due Date is shown beside each configured Fee Head in Billing Periods.
- Helper text explicitly explains that installment due dates override payment timing when installments are scheduled.
- Fee Demand expanded Fee Head rows show the snapshotted Standard Due Date.

## Late Fine dependency
ADR 158 Late Fine is not QA-final yet. ADR 158 currently calculates against installment due dates only. After ADR 160 QA PASS, Late Fine must be revised so:
- installment schedule exists → installment due date is the fine source;
- no installment schedule → Fee Demand Item Standard Due Date is the fine source;
- Late Fine rule scope must remain aligned with the effective Billing Period + Fee Head policy.

Do not begin Payment Collection + Allocation until this due-date foundation and the revised Late Fine flow pass QA.

## Test Data Cleanup
No new standalone test-data master/table is introduced. Standard Due Dates are Fee Setup configuration and must not be removed by ordinary transactional cleanup. Demand due-date snapshots are removed naturally when the corresponding QA Fee Demand is safely cleaned. Full Academic Reset follows existing Fee Setup / Fee Demand dependency order.

## Migration
`2026_09_09_150000_add_fee_due_dates_to_fee_setup_and_demands.php`

Adds nullable date columns to:
- `fee_structure_items.due_date`
- `fee_structure_item_period_settings.due_date`
- `fee_demand_items.due_date`

The columns are nullable at schema level only for backward compatibility with existing records. Application validation requires dates for newly configured/activated effective charges.

## Immediate QA
1. Deactivate one test Fee Structure.
2. Open one Billing Period and edit/add a Fee Head.
3. Confirm **Standard Due Date** appears using project DatePicker.
4. Save without Due Date; expect validation rejection for an ACTIVE charge.
5. Save with Due Date; reopen and confirm persistence.
6. For a recurring Fee Head, configure different due dates in two periods and confirm each period retains its own date.
7. Try activating a structure with any ACTIVE applicable charge lacking Due Date; activation must be blocked.
8. Generate a fresh Fee Demand and confirm each expanded Fee Head shows its snapshotted Standard Due Date.
9. Change Fee Setup Due Date after the demand exists; confirm the old demand snapshot does not change.
10. Confirm existing installment schedule due dates remain unchanged and continue to control installment timing.
