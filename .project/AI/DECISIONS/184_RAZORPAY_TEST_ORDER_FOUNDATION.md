# ADR 184 — Razorpay TEST Order Foundation

## Status
Implemented; QA pending.

## Context
ADR 177–183 established provider-aware payment-gateway configuration, multiple credential profiles per provider, and independent Fee Head routing. Configuration QA passed for Razorpay, Cashfree and PayU. The next phase starts actual provider API integration without enabling LIVE money movement prematurely.

## Decision
1. Start provider execution with Razorpay TEST mode.
2. Add a provider-isolated `RazorpayGatewayService` using Razorpay Orders API over server-side Basic Auth.
3. Add `online_payment_transactions` as the provider-transaction/audit boundary. It is separate from `fee_payments`; no fee receipt/accounting posting occurs merely because a gateway Order exists.
4. Add a manage-only `Create ₹1 Razorpay TEST order` action on an ACTIVE Razorpay TEST credential profile.
5. LIVE order creation is fail-closed in this ADR.
6. Credentials remain encrypted/hidden and are never returned to the browser.
7. Razorpay Checkout signature verification primitive is implemented server-side using HMAC-SHA256 over the server-known order id + payment id. Checkout/payment posting itself follows in the next ADR.
8. Test Data Cleanup full reset removes local `online_payment_transactions` but preserves gateway credentials and Fee Head routing because those are configuration/master data.

## Security / Accounting boundaries
- Browser never receives Key Secret.
- A created Razorpay Order is NOT a posted Fee Payment.
- Existing `FeePaymentService` remains the only accounting posting/allocation authority.
- Future verified online success must call the existing payment/allocation domain instead of creating parallel fee arithmetic.
- Webhook idempotency and raw-body signature validation are required before LIVE enablement.

## QA
1. Configure an actual Razorpay TEST Key ID + Key Secret in an INACTIVE profile.
2. Activate profile.
3. Click flask icon (`Create ₹1 Razorpay TEST order`).
4. Expected: stay inside ERP, success toast contains a Razorpay `order_...` id.
5. Invalid TEST credentials must remain in-app and show a provider error; no Laravel debug page.
6. LIVE profile must be blocked by this action.

## Next
ADR 185: fee-linked Razorpay TEST checkout, server-side checkout signature verification, idempotent verification, and posting into existing FeePaymentService. Then webhook reconciliation.
