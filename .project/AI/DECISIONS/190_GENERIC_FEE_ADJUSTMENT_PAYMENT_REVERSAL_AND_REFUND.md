# ADR 190 — Generic Fee Adjustment, Payment Reversal and Refund

**Status:** IMPLEMENTED / OWNER QA PASS / CLOSED
**Date:** 2026-09-12

## Decision
Implement the next frozen Fee Phase workflow as three separate auditable financial operations:

1. **Manual Adjustment** — CREDIT lowers student principal liability; DEBIT increases it. It never rewrites the immutable gross Fee Demand Item.
2. **Payment Reversal** — corrects an entire erroneous POSTED receipt. All original allocations remain for audit, the receipt becomes REVERSED, installment paid values are restored, and Demand paid/outstanding is recalculated.
3. **Refund** — returns part/all of money previously paid. Refund allocation is restricted to the original Fee Demand Item snapshot `is_refundable = true`; non-refundable allocations are never consumed by the refund allocator.

## Persistence
- `fee_adjustments`
- `fee_payment_refunds`
- `fee_payment_refund_allocations`

Refund allocation points back to the original `fee_payment_allocation_id`, preserving exact Demand Item / Installment / Late Fine provenance.

## Balance contract
Gross demand remains immutable.

`Net Adjusted = Approved Benefits + Posted CREDIT Adjustments - Posted DEBIT Adjustments`

`Net Paid Principal = POSTED principal Payment Allocations - POSTED principal Refund Allocations`

`Outstanding = max(Gross Demand - Net Paid Principal - Net Adjusted, 0)`

A payment reversal removes that receipt from POSTED paid principal by status; its original ledger credit remains visible and an equal reversal debit is emitted.

## Installments
Generic adjustments rebalance ACTIVE installment amounts proportionally through the existing installment service. Already-paid installment values are floors and are never reduced below paid amount. Refund/reversal restores installment `paid_amount` by the exact refunded/reversed allocation amount.

## Ledger
ADR 189 Student Fee Ledger is extended, not replaced. It now shows Adjustment, Adjustment Reversal, Payment Reversal and Refund rows in chronological running balance.

## RBAC
- `college_fee_adjustment.view`
- `college_fee_adjustment.post`
- `college_fee_adjustment.reverse`
- `college_fee_refund.post`

All are College-scoped and College-delegable; mutating permissions are sensitive.

## Test Data Cleanup
Adjustments and Refunds are independently visible/cleanable. A test Payment with Refund children must have those Refunds cleaned first. Cleanup restores installment paid values and recalculates Demand balances.

## Explicit exclusions
- No automatic provider-side LIVE gateway refund API is introduced by this ADR.
- Refund `GATEWAY` mode records the ERP-side refund only; provider refund automation requires a later provider-specific ADR and QA.
- No Fee Clearance or Student Enrollment behavior is introduced here.

## Next gate
Owner QA must pass before implementing Fee Clearance.

## QA clarification — Manual-adjustment eligibility (2026-09-13)
Manual Adjustment is intentionally limited to current liability: only non-cancelled Fee Demands with authoritative outstanding greater than zero are selectable, and only Fee Demand Items with remaining adjustable principal liability are selectable within them. The controller enforces the same eligibility on POST so this cannot be bypassed by a crafted request. This rule applies only to Manual Adjustment; fully paid receipts remain visible to the independent Reversal / Refund workflow where otherwise eligible.

## QA hardening — same-day ledger transaction order (2026-09-14)
Owner QA exposed that date-only adjustment dates could cause multiple same-day Adjustment/Reversal events to be ordered by ledger type priority instead of actual posting sequence. The authoritative Fee Demand balances were correct, but historical row balances could be attached to the wrong same-day event. ADR 190 therefore requires ADR 189's projection to use persisted event time for same-day chronology: posting `created_at` for date-only Adjustment/Payment/Refund events and `reversed_at` for reversals. No second ledger balance is stored and no finance accounting formula changes.

## QA clarification — installment adjustment invariant (2026-09-14)
For any POSTED generic adjustment on an installment-enabled Fee Demand Item:
1. the sum of ACTIVE installment `amount` values must equal that Fee Head's post-benefit/post-adjustment net liability;
2. each installment `amount` must be greater than or equal to its already-posted `paid_amount`;
3. if a proportional share would fall below a paid floor, that row is fixed at the paid floor and the remaining target is redistributed across the remaining installments;
4. Due Groups / collection availability must never exceed the authoritative Fee Head principal open balance;
5. new installment schedules persist a stable allocation percentage so later adjustments/reversals do not compound proportions from already-mutated installment amounts.

QA example: ₹20,000 Tuition, two ₹10,000 installments, ₹5,000 already paid against #1, cumulative ₹12,000 CREDIT relief. Net Tuition liability is ₹8,000. Correct installment liability is ₹5,000 (paid floor) + ₹3,000, leaving ₹3,000 Tuition outstanding. With an untouched ₹2,000 Library Fee, authoritative demand outstanding is ₹5,000.

## Owner QA refinement — installment reversal must use original schedule basis (2026-09-14)
QA confirmed that generic adjustment reversal must be symmetrical with adjustment application. Reversal recalculates the Fee Head liability from the original installment allocation percentages, subject to immutable paid floors. It must not use already-adjusted installment amounts as the new weighting basis. Due dates only classify open balances as overdue/current/upcoming and never alter the allocation basis. Legacy schedules without stored percentages recover their original allocation from audit history and persist it for future cycles. See ADR 193.


## Owner QA progress and remaining gate (2026-09-14)
Manual CREDIT/DEBIT posting and reversal, installment paid-floor behavior, stable original installment allocation on reversal, full Payment Reversal, partial Refund, over-refund rejection, and reversal-after-refund blocking have passed owner QA after the documented fixes. Separate fully refundable and fully non-refundable receipt behavior has also been observed correctly.

ADR 190 remains `IMPLEMENTED / OWNER QA REQUIRED` only because the following targeted checks remain:
- one **mixed-allocation receipt** containing both refundable and non-refundable Fee Heads;
- the four ADR 190 RBAC permissions and College scoping;
- dependency-safe Test Data Cleanup re-test after ADR 194;
- final cross-screen reconciliation spot-check.

## Test Data Cleanup QA correction (ADR 194)
A reversed Fee Payment intentionally retains its original `fee_payment_allocations` for audit, but those allocations also block deletion of the referenced Installment Schedule. Therefore Test Data Cleanup must expose both POSTED and REVERSED Fee Payments. Cleaning a REVERSED receipt deletes the retained test audit rows without re-applying the installment paid restoration already performed by Payment Reversal. Refund children must still be cleaned first.

### QA update — 2026-09-14 (Test Data Cleanup online-payment dependency)
A new cleanup edge case was found after the earlier reversed-payment cleanup QA passed: a Fee Demand could still be referenced by an unposted TEST `online_payment_transactions` row, causing SQLSTATE 23000 on demand deletion. ADR 195 fixes the supported cleanup path.

**Open ADR 190 QA after this patch:**
1. Mixed single receipt containing refundable + non-refundable allocations.
2. RBAC: `college_fee_adjustment.view`, `college_fee_adjustment.post`, `college_fee_adjustment.reverse`, `college_fee_refund.post`.
3. College-scope isolation.
4. Re-test Fee Demand cleanup with an unposted TEST online-payment transaction.
5. Final Fee Demand / Due Groups / Student Fee Ledger reconciliation spot-check.



## Owner QA update — 2026-09-15 — mixed receipt PASS, delegated-staff second-refund defect found
The controlled mixed-allocation receipt QA is now **PASS**. Owner QA created one ₹22,000 receipt containing ₹20,000 Tuition marked non-refundable and ₹2,000 Library Fee marked refundable. The Reversal / Refund register correctly exposed only ₹2,000 as refundable. A ₹500 refund restored only Library Fee liability, leaving Tuition fully settled and ₹500 Library outstanding across Fee Demand and Due Groups.

A subsequent delegated-College-staff QA exposed a separate cumulative-refund defect on the same receipt: attempting a second refund failed with `Undefined property: stdClass::$amount` in `FeeAdjustmentRefundService`. The implementation fix must explicitly select/alias the original payment-allocation amount used by the remaining-refundable calculation and subtract all POSTED refund allocations for that original allocation. This is documented by ADR 196.

Audit verification is part of the same owner QA gate. Refund, manual adjustment reversal, and full payment reversal must remain auditable under the exact College scope even when the actor is a delegated College staff user rather than the original College administrator. The audit record must preserve the actor and financial resource context without creating a parallel audit subsystem.

**Owner re-test confirmation — 2026-09-15:**
- Second/subsequent refund after ADR 196 — **PASS**; repeat refund now posts correctly without the prior aggregate/property HTTP 500.
- ADR 190 finance RBAC with another staff user of the same College — **PASS** for `college_fee_adjustment.view`, `college_fee_adjustment.post`, `college_fee_adjustment.reverse`, and `college_fee_refund.post`.
- Test Data Cleanup — **PASS** in owner re-test, including the previously confirmed reversed-payment cleanup path.

**Current ADR 190 remaining owner QA:**
1. Verify delegated-staff refund/reversal audit events in the College Audit Log, unless already separately confirmed by owner.
2. College-scope isolation / cross-College denial.
3. Re-test ADR 195 unposted TEST online-payment transaction → Fee Demand cleanup path if that exact edge has not been separately confirmed.
4. Final Fee Demand / Due Groups / Student Fee Ledger reconciliation spot-check.

Fee Clearance remains blocked until these remaining owner QA gates are closed.


## Final owner QA closure — 2026-09-15
**ADR 190 is formally CLOSED: IMPLEMENTED / OWNER QA PASS.**

Owner confirmation closes every remaining targeted gate: mixed-allocation receipt behavior, repeat/cumulative refund, all four ADR 190 finance permissions for delegated College staff, College-scope isolation/cross-College denial, College Audit Log recording with the delegated actor, Test Data Cleanup including ADR 194 and the ADR 195 unposted TEST online-payment dependency path, and final Fee Demand / Due Groups / Student Fee Ledger reconciliation.

All earlier open/pending lists in this ADR are historical QA progress and are superseded by this final closure. Fee Clearance may now begin as the next Fee Phase implementation; this ADR does not itself implement Fee Clearance.
