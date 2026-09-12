# ADR 180 — Multiple Gateway Credential Profiles and Fee Head Routing

**Date:** 2026-09-10  
**Status:** IMPLEMENTED / OWNER QA REQUIRED

## Decision
A College may configure multiple credential profiles for the same payment-gateway provider and environment.

This models the real institutional setup where one Razorpay account/login can expose different Key ID + Secret pairs for different collection purposes/accounts (for example principal collections, alumni fund, add-on course, remittance) without the provider giving one shared merchant code.

`college_payment_gateways` therefore represents a **gateway credential profile**, not a unique provider installation.

## Contract
- Providers remain Razorpay, Cashfree Payments and PayU.
- The same College may create multiple Razorpay/Cashfree/PayU profiles.
- Each profile has its own:
  - Profile Name (`display_name`)
  - TEST/LIVE environment
  - optional Merchant / Account Reference when a provider supplies one
  - Key / Client ID
  - Secret
  - optional Webhook Secret
  - ACTIVE/INACTIVE lifecycle
- Existing encrypted secret storage remains unchanged.
- Fee Head mapping remains attached to an exact credential profile and may also store Product Code + optional Settlement Code.
- The same Product Code may still be reused across multiple Fee Heads.
- For the same College + Provider + Environment + Fee Head, only one profile mapping may be ACTIVE. Saving an ACTIVE mapping on one profile automatically deactivates the same Fee Head on competing profiles for that provider/environment. This prevents ambiguous provider routing.
- TEST and LIVE mappings are independent.

## Schema change
The old unique constraint `(college_id, provider)` is removed and replaced with a lookup index `(college_id, provider, environment)`.

No credential data is copied or deleted.

## UI
- `Add Gateway` is renamed to `Add Gateway Credential Profile`.
- `Display Name` is presented as `Profile Name`, with institution-purpose examples.
- `Merchant ID` is relabelled `Merchant / Account Reference (optional)` because some providers/configurations expose only credential pairs.
- Page copy explains that multiple Key ID/Secret pairs under one provider are supported.

## Accounting boundary
This ADR does not post money and does not change Fee Payment / Allocation accounting. Future provider checkout must resolve the Fee Head to its single ACTIVE credential profile and then hand verified success into the existing FeePaymentService.

## QA
1. Migrate successfully with an existing Razorpay profile already present.
2. Add a second Razorpay TEST profile with a different Profile Name; it must save.
3. Edit each profile and confirm its own Key ID / Secret state is independent.
4. Map one Fee Head ACTIVE on profile A, then ACTIVE on profile B; profile A mapping must become INACTIVE for that Fee Head.
5. Confirm another Fee Head can remain ACTIVE on profile A.
6. Confirm same Product Code across heads still saves.
7. Confirm missing credentials still block activation with in-app toast (ADR 179 regression).
