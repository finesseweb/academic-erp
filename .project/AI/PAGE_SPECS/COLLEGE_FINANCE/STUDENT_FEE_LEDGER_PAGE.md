# Student Fee Ledger Page

## Status
- Implementation: IMPLEMENTED
- Review Status: OWNER_QA_REQUIRED
- Decision: ADR 189

## Route
`GET /college/{college}/fee-ledger`

## Scope
College-scoped finance page. Requires `college_fee_ledger.view`.

## Purpose
Provide one searchable student-wise chronological financial statement from existing authoritative Fees Phase transactions. This page is read-only and must never become a second accounting store.

## Inputs / filters
- Active Academic Session; current session by default.
- Student search across candidate name, application number, admission number, email, phone, Fee Demand number and billing-period label.
- Server pagination: 25 / 50 / 100.
- Selected Admission/Student.

## Student register
Show:
- Student name/contact context.
- Application / Admission number.
- Programme / Session.
- Fee Demand count.
- Total Debit.
- Total Credit.
- Current Balance.
- `View Ledger` action.

## Ledger statement
Summary:
- Student / Admission / Application.
- Programme / Session.
- Total Debit.
- Total Credit.
- Outstanding Balance.

Entry columns:
- Effective Date.
- Transaction Type.
- Reference.
- Description / Fee Head context.
- Due Date where applicable.
- Debit.
- Credit.
- Running Balance.

## Source rules
- Fee Demand Item => Debit.
- APPROVED Student Benefit Item sanctioned amount => Credit.
- ACTIVE Late Fine Charge => Debit.
- POSTED Fee Payment Allocation => Credit.
- Verified online payment is represented only by its posted Fee Payment allocations.

## Lifecycle history
- CANCELLED Fee Demand: keep original Demand debit and add a cancellation Credit.
- APPROVED then CANCELLED Benefit: keep original Benefit Credit and add a cancellation Debit.

## Exclusions
- PENDING / REJECTED Benefits that were never approved.
- CANCELLED Benefits that were never approved.
- SUPERSEDED / REVERSED Late Fine rows.
- Non-POSTED payments.
- Gateway credential/order/test rows without a posted Fee Payment.

## UX requirements
- Use existing ERP application shell/theme tokens.
- Server pagination and search; do not load every student.
- Professional empty state when no student is selected or no rows match.
- Permission-aware direct actions.
- Responsive horizontal tables for detailed finance rows.
- No edit/delete controls on ledger rows.

## Dependencies
- Fee Demand + Demand Items.
- Student Benefits.
- Installment/Late Fine domain.
- Fee Payment + Allocations.
- ADR 188 verified online posting.

## Deferred
Adjustment/Reversal/Refund, Fee Clearance, ledger export/print and Student Portal fee statement.
