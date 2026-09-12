# ADR 171 — Payment Collection + Deterministic Allocation

## Status
Implemented for QA. This is the next Fees Phase step after ADR 170 due-grouping contract and Late Fine core QA.

## Purpose
Allow College finance users to receive one payment against a Fee Demand while preserving exact accounting allocation to Fee Demand Item, Installment and Late Fine sources.

## Canonical payable chain
Fee Setup → Fee Demand Item → Approved Benefit → ACTIVE Installment (if any) → Due Group → Payment → Allocation → Fee Demand/Installment balances → Student Ledger (future).

## Non-negotiable invariants
1. Gross Fee Demand and Fee Demand Item amounts are never rewritten by payment.
2. Same-date Fee Heads/installments may be grouped in the UI, but accounting liabilities are not merged.
3. ACTIVE installment due date is authoritative when an installment schedule exists. Parent Fee Demand Item due date is not double-counted.
4. Without installments, the Fee Demand Item Standard Due Date snapshot remains the payable date.
5. Approved benefits reduce the open principal before collection.
6. Mandatory and optional amounts remain distinct. Optional charges are not automatically consumed unless `include_optional=true`.
7. One payment can allocate across several same-date Fee Heads/installments.
8. Partial payment is allowed. Overpayment beyond the selected payable set is blocked.
9. Payment allocation order is deterministic and category-safe across the whole selected payable set: Mandatory Principal → Mandatory Late Fine → Optional Principal → Optional Late Fine. Within each category, oldest due date is consumed first; stable Fee Demand Item ordering breaks same-date ties. This prevents an earlier-dated optional charge from consuming money before any selected mandatory principal, including selected future mandatory principal.
10. `fee_demands.paid_amount` contains PRINCIPAL payment only. Late Fine payment does not reduce principal outstanding.
11. `fee_demands.outstanding_amount = total_amount - principal_paid - approved_adjustments`.
12. Installment principal allocations increment `fee_installment_schedules.paid_amount`.
13. Late Fine remains a separate auditable charge; payment allocation against it is separately stored.
14. A posted Late Fine revision that has payment allocation is crystallized and cannot be silently superseded/reversed by calculator reruns. Future explicit reversal/adjustment workflows must handle corrections.
15. Posted payment is immutable in this ADR. General adjustment/reversal/refund remains a later Fees Phase step.

## Storage
### `fee_payments`
Stable receipt/collection header: college, admission, session, receipt number, payment date, amount, currency, mode, external reference, status, notes, collector and future reversal metadata.

### `fee_payment_allocations`
Exact allocation ledger. Each row points to one Fee Demand + Fee Demand Item and optionally one Installment or Late Fine Charge. `source_type` is `DEMAND_ITEM`, `INSTALLMENT`, or `LATE_FINE`.

## Payment modes
CASH, CARD, UPI, BANK_TRANSFER, CHEQUE, OTHER.

## Receipt numbering
Generated as `RCP-YYYYMMDD-<college-id>-<5-digit-sequence>` inside the posting transaction.

## Permissions
- `college_fee_payment.view`
- `college_fee_payment.collect`

## User interface
New College Fee Management page: **Payment Collection**.
- Current Session default.
- Search student/application/admission/demand.
- Server pagination.
- Shows ADR 170 Due Groups.
- Shows Mandatory, Optional and Late Fine open amounts separately.
- `Collect Payment` dialog defaults to mandatory + mandatory late fine.
- Optional charges require explicit opt-in.
- Late Fine may be excluded explicitly.
- Amount can be lower than selected available balance for partial payment.
- Posted payment register shows stable receipt, mode/reference, amount and allocations.

## Late Fine integration
`FeeLateFineService::activeFineForDemandIds()` now returns ACTIVE **unpaid** fine (fine amount minus POSTED Late Fine allocations). A paid fine revision is protected from silent recalculation mutation.

## Test Data Cleanup
New cleanable module: **Fee Payments / Receipts**.
Cleaning a QA payment:
- removes its allocation rows and payment header,
- reverses INSTALLMENT `paid_amount` contributed by the payment,
- recomputes Fee Demand principal `paid_amount`, `outstanding_amount`, and status from remaining POSTED allocations,
- preserves Fee Setup / real masters,
- writes audit event `TEST_FEE_PAYMENT_CLEANED`.
Late Fine or Installment cleanup is blocked while payment allocations still reference those rows; payment must be cleaned first.

## Explicitly deferred
- Online payment gateway orchestration/webhooks.
- General payment reversal/refund/adjustment workflow.
- Student Fee Ledger presentation.
- Receipt PDF/printing (receipt number and register foundation are in place).
- Cross-demand single receipt. ADR 171 payment is exact to one Fee Demand; future ledger/payment UX may aggregate multiple demands while preserving allocations.

## QA gate
Payment Collection + Allocation must PASS QA before moving to Online Payment Gateways.
