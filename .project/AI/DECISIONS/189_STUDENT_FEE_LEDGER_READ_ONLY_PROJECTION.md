# ADR 189 — Student Fee Ledger as Read-Only Financial Projection

**Date:** 2026-09-12  
**Status:** IMPLEMENTED / OWNER QA REQUIRED

## Context
Payment Collection, approved Student Benefits, Installments, Late Fine and verified online payments now exist as authoritative financial transactions. The next Fees Phase milestone is a Student Fee Ledger that exposes one chronological statement without creating a second accounting engine.

## Decision
1. Student Fee Ledger is a **read-only projection**. No `fee_ledger` table is created.
2. Ledger debit sources are:
   - Fee Demand Items at their Fee Demand generation date;
   - ACTIVE Late Fine Charges at their calculation date;
   - cancellation reversal of a previously approved Benefit.
3. Ledger credit sources are:
   - APPROVED Student Benefit Item sanctioned amounts at benefit decision date;
   - POSTED Fee Payment Allocations at the payment date;
   - cancellation of a Fee Demand, reversing its original Demand Item debit.
4. Payment allocations are shown individually so principal, installment and Late Fine allocation remain traceable to their exact accounting source.
5. Online gateway attempts/orders are not separate ledger credits. A verified online payment appears only after ADR 188 posts the normal `fee_payments` / `fee_payment_allocations` receipt, preventing double counting.
6. CANCELLED Fee Demands preserve history as original debit plus cancellation credit. A Benefit that was approved and later CANCELLED preserves its original credit plus cancellation debit. Benefits never approved, non-ACTIVE/superseded Late Fine revisions and non-POSTED Payments do not affect the ledger.
7. Running balance is computed in presentation order from authoritative debits minus credits. The ledger never writes back to Fee Demand/Installment/Payment state.
8. Same-day display order is deterministic: Demand → Benefit → Late Fine → Payment, then stable record/sequence ordering.
9. Ledger is College scoped and session filterable. Cross-College access is rejected server-side.
10. Permission: `college_fee_ledger.view`.
11. Payment Collection provides a permission-aware direct `Ledger` link for each student; Fee Management sidebar also exposes `Student Fee Ledger`.

## Route and implementation
- Route: `GET /college/{college}/fee-ledger`
- Controller: `CollegeFeeLedgerController`
- Service: `FeeLedgerService`
- Page: `resources/js/pages/college-fee-ledger/index.tsx`
- Admission relation: `Admission::feeDemands()`

## Accounting invariants
- Gross Fee Demand is never rewritten by the ledger.
- Benefits remain adjustments, not negative Fee Demands.
- Late Fine remains a separate auditable liability.
- Payments remain represented by deterministic payment allocations.
- No provider transaction is counted before accounting posting.
- The ledger can be rebuilt at any time from source transactions.

## Explicitly deferred
- Generic adjustment / reversal / refund workflow.
- Fee Clearance.
- Receipt PDF/ledger export/printing.
- Student self-service ledger exposure.

## QA gate
1. Run migration and rebuild frontend.
2. Verify College Admin/Super Admin can open Student Fee Ledger.
3. Verify a role without `college_fee_ledger.view` cannot open it and does not see the sidebar/direct link.
4. Pick a student with Fee Demand only; total debit and balance must equal the active Demand Item total.
5. Pick a student with approved Benefit; benefit must appear as credit and reduce running balance exactly by sanctioned item amount.
6. Pick a student with Late Fine; ACTIVE fine must appear as debit.
7. Post an offline partial payment; each deterministic allocation must appear as credit and final balance must match current outstanding principal + outstanding active fine.
8. Complete one ADR 188 TEST online payment; confirm the ledger shows only the resulting normal receipt allocations, not the gateway order separately.
9. Confirm a cancelled Fee Demand shows its original debit plus cancellation credit, and an approved-then-cancelled Benefit shows its original credit plus cancellation debit. A Benefit never approved must remain excluded.
10. Confirm session filter and student/application/admission/demand search work.
11. Confirm Payment Collection → Ledger opens the correct student/session.
