# ADR 196 — Cumulative Refund Allocation and Delegated-Staff Audit Integrity

**Status:** IMPLEMENTED / OWNER QA PASS / CLOSED  
**Date:** 2026-09-15

## Context
ADR 190 mixed-receipt QA passed for the first refund: one ₹22,000 receipt contained ₹20,000 non-refundable Tuition and ₹2,000 refundable Library Fee, and only ₹2,000 was exposed as refundable. A ₹500 refund correctly reopened only ₹500 of Library Fee liability.

When a different staff user in the same College attempted a second refund against the remaining refundable allocation, the request failed with HTTP 500: `Undefined property: stdClass::$amount` in `FeeAdjustmentRefundService`. The cumulative remaining-refundable calculation depended on an allocation amount property that was not explicitly guaranteed by the query result contract.

The same QA cycle requires verification that sensitive finance mutations performed by delegated College staff are visible in the existing College-scoped Audit Log with the actual actor preserved.

## Decision
1. Cumulative refund availability is calculated from the original refundable `fee_payment_allocations`, minus all POSTED `fee_payment_refund_allocations` already consumed from each original payment allocation.
2. The service query must explicitly select/alias the original allocation amount consumed by this calculation. Business logic must not rely on an incidental `stdClass` property produced by `a.*` or an ambiguous joined column.
3. A second or later refund is subject to exactly the same refundable Fee Head boundary as the first refund. Non-refundable allocations can never become refundable because another refund already exists.
4. Over-refund remains blocked against the cumulative remaining refundable balance.
5. The fix must not rewrite prior posted refunds or payment allocations. Existing financial rows remain immutable/auditable.
6. Refund, manual adjustment reversal and full payment reversal continue to use the project's existing audit subsystem. Delegated College staff actions must carry the exact College scope and actual actor user so they appear in that College's authorized Audit Log; no module-specific parallel audit table/log is introduced.
7. Audit metadata for a successful refund should preserve safe financial context sufficient for investigation, including payment/receipt reference, refund resource/reference, amount, reason, mode/reference where applicable, and before/after refundable context when available. Secrets and payment credentials are never audited.
8. A failed refund that never posts must not create a posted financial refund transaction. Existing general request/security logging conventions remain unchanged.

## QA acceptance
Using the already-proven mixed receipt:
- original receipt paid = ₹22,000; original refundable allocation = ₹2,000;
- after first ₹500 refund, remaining refundable = ₹1,500;
- a second ₹500 refund by another authorized staff user in the same College must post without HTTP 500;
- remaining refundable must become ₹1,000;
- only refundable Library liability must reopen by the additional ₹500; Tuition must remain fully settled;
- Student Fee Ledger, Fee Demand and Due Groups must reconcile;
- College Audit Log must show the successful refund under the correct College scope and identify the delegated staff actor;
- attempting more than the remaining refundable balance must still be rejected cleanly.

## Non-goals
- No new refund approval workflow is introduced.
- No provider-side automatic refund is introduced.
- No new audit subsystem is introduced.
- No change to Fee Head refundable policy semantics.


## Owner QA result — 2026-09-15
- **PASS:** second/subsequent refund after the cumulative-refund hotfix. The owner confirmed the repeat refund now works correctly and the previous `stdClass::$amount` HTTP 500 is resolved.
- **PASS:** delegated College-staff RBAC on the ADR 190 finance surface.
- **PASS:** Test Data Cleanup current owner re-test.
- **PENDING:** visual/record-level confirmation that the delegated-staff refund/reversal event appears in the College Audit Log with the correct actor and College scope, unless the owner separately confirms that evidence.


## Final audit QA result — 2026-09-15
- **PASS:** owner confirmed the delegated-staff finance event is recorded correctly in the College Audit Log.
- The correct delegated actor and College-scoped finance context are visible through the existing audit subsystem.
- Together with the already-passed repeat-refund, RBAC and cleanup checks, ADR 196 is **CLOSED / OWNER QA PASS**.
