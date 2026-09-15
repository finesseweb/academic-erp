# ADR 174 — Payment Collection Upcoming Due Visibility

## Status
Implemented — pending QA.

## Decision
Payment Collection must show the earliest unpaid future principal due date and amount before it becomes payable. This is informational only and does not change accounting liability, due dates, or allocation rules.

## Behaviour
- Student-grouped Open Payables row shows `Next Due` using the earliest future unpaid Due Group across that student's open Fee Demands in the selected session.
- If more than one demand has principal due on that same earliest date, the student-level Next Due amount is aggregated for that date.
- Expanded demand row also shows that demand's earliest future unpaid due date and amount.
- `Next Due` is calculated server-side using the application's current date, avoiding browser timezone drift.
- Future principal remains excluded from default `Due Now` collection.
- Cashier may explicitly select `Include future dues` to collect it in advance.
- Once the date is reached, that liability stops being a future due and naturally participates in Due Now. Applicable Late Fine/grace rules continue to operate independently.
- A fully paid future installment disappears from Next Due and the following future due becomes the next one automatically.

## Invariants
- No Fee Demand, Fee Demand Item, or Installment amount is edited by this feature.
- No new liability is created.
- Partial payment remains allowed.
- Overpayment remains blocked by Payment Collection allocation rules.
- No database migration required.
