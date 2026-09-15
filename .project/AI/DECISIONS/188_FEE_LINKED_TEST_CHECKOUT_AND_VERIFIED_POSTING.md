# ADR 188 — Fee-Linked TEST Checkout and Verified Posting

**Date:** 2026-09-11  
**Status:** IMPLEMENTED / OWNER QA REQUIRED

## Context
ADR 184 and ADR 187 proved provider-side TEST connectivity for the three retained payment gateways: Razorpay, Cashfree and PayU. Those credential tests deliberately did not create Fee Payments. The next step is to connect an actual Fee Demand selection to provider checkout while preserving the existing deterministic Fee Payment / Allocation engine as the only accounting authority.

## Decision
1. Online fee checkout is initiated from the existing **Payment Collection & Allocation** dialog. Offline collection remains unchanged.
2. The same amount/date/optional/late-fine/future selection used by offline collection is previewed through the existing `FeePaymentService` allocation rules before any gateway order is created.
3. Fee Head routing is resolved from ACTIVE `fee_head_gateway_mappings` in the **TEST** environment.
4. One provider checkout may use only one credential profile. If the selected allocation spans Fee Heads routed to different credential profiles, checkout is rejected and the user must collect the routed groups separately.
5. Unassigned Fee Heads are not silently routed; online checkout is rejected until routing is configured.
6. LIVE checkout remains blocked. This ADR is TEST-only until owner QA and webhook runtime QA are completed.
7. `online_payment_transactions` is linked to the Fee Demand, Admission and eventual Fee Payment. A provider order/payment never becomes accounting merely because checkout reports success.
8. Provider verification is server-side before posting:
   - Razorpay: Checkout signature + server payment fetch; order, amount, currency and `captured` status must match.
   - Cashfree: server Get Order + Order Payments; order must be `PAID`, amount/currency must match and a SUCCESS payment must exist.
   - PayU: reverse-hash callback verification + server `verify_payment`; verified status/amount must match.
9. Only after verification does the system call the existing `FeePaymentService::collect()` to create the receipt and deterministic allocations.
10. Posting is idempotent through `online_payment_transactions.fee_payment_id`. Repeated verified callbacks return the already-posted Fee Payment rather than posting twice.
11. If provider payment is verified but ERP posting cannot complete because the liability changed meanwhile, the transaction is retained as `PAYMENT_VERIFIED_UNPOSTED` for reconciliation; provider money is never silently discarded.
12. Webhooks may use the same verified-posting service. Runtime webhook delivery QA remains deferred until a public HTTPS endpoint is available.

## Provider checkout UX
- Razorpay Standard Checkout is opened from the ERP using the server-created order and public Key ID only; Key Secret never reaches the browser.
- Cashfree Web Checkout uses the server-created `payment_session_id` in Sandbox mode.
- PayU Hosted Checkout is submitted as a signed form to the TEST `_payment` endpoint; the return callback is verified server-side.

## Schema
`online_payment_transactions` gains nullable links to:
- `fee_demand_id`
- `admission_id`
- `fee_payment_id`
- `verified_at`
- `posted_at`

Credential-test rows remain valid with these fields null.

## Security / accounting invariants
- Secrets remain encrypted and server-only.
- Client success callbacks are never trusted by themselves.
- Server-known provider order/transaction identifiers and amounts are authoritative.
- Fee Head routing controls the external credential profile; `FeePaymentService` controls ERP allocation.
- LIVE money movement is fail-closed in this ADR.

## QA gate
1. Run migration and clear caches/build frontend.
2. Choose one Fee Demand whose selected payable rows all route to one ACTIVE TEST credential profile.
3. Initiate online payment and confirm provider checkout opens automatically according to Fee Head routing.
4. Complete a TEST payment.
5. Confirm `online_payment_transactions` reaches `PAYMENT_POSTED`, has provider payment ID, `fee_payment_id`, `verified_at`, `posted_at`.
6. Confirm exactly one Fee Payment receipt exists and its allocations match the existing offline allocation rules.
7. Refresh/replay provider verification and confirm no duplicate Fee Payment is created.
8. Test a selection spanning two credential profiles; checkout must be blocked before provider order creation.
9. Test an unassigned Fee Head; checkout must be blocked with an in-app message.
10. Confirm offline Payment Collection still works unchanged.
11. Run Fee Payment Test Data Cleanup on the online receipt and confirm linked online transaction is removed and balances are restored.
12. After direct checkout QA passes for Razorpay, Cashfree and PayU, perform public-HTTPS webhook QA before enabling LIVE.
