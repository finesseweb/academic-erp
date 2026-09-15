# ADR 182 — Fee Head Independent Gateway Credential Routing

**Date:** 2026-09-10  
**Status:** IMPLEMENTED / OWNER QA REQUIRED

## Decision
Payment-gateway credential assignment is a **Fee Head-level routing decision**. A College is not required to send every Fee Head through one Razorpay/Cashfree/PayU credential profile.

A provider login may expose several Key ID + Secret pairs. These are stored as credential profiles, and each Fee Head independently selects the profile that should receive that Fee Head's collection.

## UI contract
- Credential profile cards only manage provider credentials/lifecycle.
- Fee Head mapping is moved into one explicit **Fee Head Payment Routing** register.
- Routing is separated by `TEST` and `LIVE` environment.
- Every Fee Head row has:
  - Credential Profile selector
  - Product Code
  - optional Settlement Code
- `Not assigned` is valid. A Fee Head does not have to be online-payable through a profile.
- Different Fee Heads may point to different profiles, including profiles under the same Razorpay login/provider.
- `Save All Routing` persists the routing register in one action.

## Routing invariant
Within one College + Environment, one Fee Head has at most one ACTIVE credential-profile route in this V1 routing model. Selecting another profile deactivates the previous active route for that Fee Head in that environment. TEST and LIVE routing remain independent.

This removes the misleading earlier presentation where every credential-profile card displayed every Fee Head and could suggest that all heads belonged to that profile.

## Validation
- An assigned Fee Head requires Product Code.
- Selected profile must belong to the same College and selected TEST/LIVE environment.
- Invalid routing returns an in-app toast; no raw 422/debug page.
- Unassigning a Fee Head deactivates the existing active route for that environment without deleting mapping history/configuration.

## Accounting boundary
No Fee Demand, Fee Demand Item, Late Fine, Fee Payment or Payment Allocation accounting is changed by this ADR. Actual gateway checkout remains a later implementation and will resolve the Fee Head route before creating provider transactions.

## QA
1. Configure at least two Razorpay TEST credential profiles.
2. Open Fee Head Payment Routing → TEST.
3. Route Tuition Fee to profile A and Alumni/another Fee Head to profile B.
4. Save All Routing and refresh; each Fee Head must retain its own selected profile.
5. Set another Fee Head to Not assigned; it must remain unassigned after refresh.
6. Switch to LIVE; TEST selections must not leak into LIVE.
7. Assigned row with blank Product Code must be blocked with an in-app error toast.
