# ADR 102 — Fee Structure Period-First Configuration UI

Date: 2026-09-04
Status: Accepted / Implemented

## Decision
Fee Structure configuration is presented as:

`Fee Structure -> Billing Periods -> Fee Items`

The user must not manage recurring charges through a hidden "default amount + overrides" mental model. Billing periods are shown first and Fee Items are configured inside the exact period in which they apply.

## Period source
- College `PER_TERM`: billing periods are only the ACTIVE Curriculum Terms of the exact College Program Offering.
- College `PER_ACADEMIC_YEAR`: billing years are derived only from complete ACTIVE Curriculum term groups of that offering. Missing future Curriculum terms do not create future billing periods.
- University `PER_TERM` / `PER_ACADEMIC_YEAR`: because a University Fee Structure has no exact College Curriculum, its period template is derived from the selected Program Template term structure/duration. When later applied to a College, operational applicability must still intersect with that College Offering's actual Curriculum.
- `ONE_TIME` exposes one billing period: One-Time / Whole Applicable Scope.
- `SPECIFIC_TERM` / `SPECIFIC_ACADEMIC_YEAR` expose only the selected period.

## Fee Item behavior
- A Fee Head can exist in one billing period and be absent in another.
- The same Fee Head can carry different amounts in different periods.
- Adding an already-used Fee Head to another period reuses the same parent Fee Structure Item and updates only period applicability/amount; duplicate Fee Heads are not created.
- Existing `fee_structure_item_period_amounts` and `fee_structure_item_period_exclusions` remain the normalized storage for period amount/applicability, preserving backward compatibility.
- Not Applicable means no future Fee Demand line for that Fee Head/period; it is not a zero-value fee.

## UX
- Structure cards expand to `Billing Periods`, not a flat `Fee Items` list.
- Every billing period shows its own active total and configured Fee Items.
- `Add Fee Item` is period-scoped.
- The same shared Fee Manager component applies this interaction to both University and College Fee Management.

## Consequence for Fee Demand
Future Fee Demand must resolve the exact effective billing period first, then use only Fee Items applicable to that period and snapshot their effective period amount.
