# ADR 095 — Fee Foundation and Enrollment Gate

## Status
ACCEPTED / IMPLEMENTED FOUNDATION — 2026-09-04

## Decision
Student Enrollment must not be the immediate next step after Admission Confirmation. The ERP will insert a Fee phase between confirmed admission and official student enrollment:

`Admission Confirmation -> Applicable Fee Demand -> Payment / Adjustment -> Fee Clearance -> Student Enrollment / Lifecycle`

The first implementation is the reusable Fee Foundation: Fee Heads, Fee Structures and Fee Structure Items at both University and College ownership levels.

## Ownership
### University-owned fees
University may define common/authoritative charges such as Registration, Examination, Migration, Degree or University Development charges. A University Fee Structure belongs to an Academic Session and may apply to all Programs or one Program Template.

### College-owned fees
College may define local charges such as Admission, Tuition, Library, Lab or College Development charges. A College Fee Structure belongs to one exact College Program Offering; Program Template and Academic Session are inherited from that Offering.

University-owned charges are not copied into College ownership. Future Fee Demand generation combines independently applicable ACTIVE University and College structures.

University-to-College applicability is governed by ADR 097: a University Fee Structure is explicitly `MANDATORY` or `OPTIONAL` for matching Colleges. Mandatory structures apply automatically; optional structures require explicit College adoption. If no matching University structure exists, the College may define its own without any University prerequisite.

## Fee Head
A Fee Head defines the meaning of a charge, not the amount. Fee Head ownership is either University (`college_id = null`) or exact College (`college_id` set). New Fee Heads start `INACTIVE`; only ACTIVE Fee Heads may be used by an active structure.

Fee Category is configurable master data above Fee Head (ADR 096). The original common categories are seeded as defaults, but Fee Head no longer stores a hard-coded category string. Fee Category remains degree/program independent; Program-specific fee applicability and amount belong to Fee Structure.

## Fee Structure
A Fee Structure defines amounts and applicability. New structures start `INACTIVE`. Initial purposes are ADMISSION, ACADEMIC, EXAMINATION and OTHER. Currency is stored per structure.

Activation requires at least one ACTIVE item with amount > 0 and all used Fee Heads ACTIVE. Only one ACTIVE structure may exist for the same owner + academic scope + Academic Session + purpose.

## Fee Structure Item
Each item references one owned Fee Head and stores amount plus independent rules:
- Mandatory / Optional
- Required for Enrollment Clearance
- Installment Allowed
- ACTIVE / INACTIVE

`Required for Enrollment Clearance` is intentionally explicit. Student Enrollment will later ask Fee Clearance whether all required admission charges are cleared; it must not infer this only from fee category or payment mode.

## Online / Offline payment future contract
Payment execution is not part of this foundation milestone. The later Payment Layer must be reusable by Admission, semester fees, examination fees, certificates and other charge consumers.

Online gateways will use a common adapter/interface rather than gateway-specific code inside each module. Multiple pre-integrated gateway adapters may be shipped (for example Razorpay, Cashfree, PayU), with institution-scoped Test/Live credentials and webhook verification. University and different Colleges may use different enabled gateways.

Offline modes will include auditable manual recording such as Cash, Cheque, DD, Bank Transfer/NEFT/RTGS and POS/Counter. Enrollment will depend on Fee Clearance, not on whether payment was online or offline.

## Deferred next milestones
1. Admission Fee Demand generation from a CONFIRMED Admission and applicable ACTIVE University + College ADMISSION structures.
2. Payment Ledger: online/offline payment, partial payment, receipt, verification, reversal/refund/reconciliation.
3. Scholarship / waiver / concession / adjustment.
4. Fee Clearance state and Enrollment eligibility.
5. Student Enrollment / Lifecycle.

## Non-goals of this milestone
- No student fee demand rows yet.
- No payment gateway credentials or API calls yet.
- No payment/receipt/refund ledger yet.
- No scholarship/waiver workflow yet.
- No Student record is created from fee setup.
