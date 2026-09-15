# ADR 115 — Fee Demand generation workflow and Academic Policy resolution

## Status
Accepted — 2026-09-05

## Decision
Fee Demand is not a permanently manual, one-candidate-at-a-time process.

### Admission stage
When Admission Confirmation becomes `CONFIRMED`, Fee Management automatically attempts to generate Billing Period 1 demand from the effective University + College fee configuration.

Billing Period 1 includes:
- applicable `ONE_TIME` items;
- applicable recurring Period 1 items;
- exact period-specific amount;
- exact `is_mandatory`;
- exact `is_enrollment_clearance_required`;
- exact `installment_allowed`.

If no applicable ACTIVE fee item exists, Admission Confirmation remains valid and no demand is required. Manual initial-demand generation exists only as recovery for already-confirmed admissions that pre-date automatic generation or whose initial generation was intentionally deferred.

### Later academic periods
Later-period demands are cohort/bulk operations, not one-by-one fee actions.

Canonical flow:
`Program Offering → exact Curriculum/Term → applicable Academic Policy → Student Academic Progression result → eligible students → Bulk Fee Demand`

Fee Management must not independently calculate promotion/progression eligibility. It consumes the authoritative result produced by the Student Academic Progression / Result workflow.

Until Student Enrollment + Academic Progression persistence exists, later-period bulk generation remains intentionally unavailable. This prevents Fee Management from inventing a parallel eligibility system.

### Academic Policy resolution
College does not define or select the University Academic Policy again.

For an ACTIVE College Program Offering, the system resolves the single current `ACTIVE + APPROVED + is_current_version` policy in the same University and Academic Session using this specificity order:
1. exact Curriculum;
2. exact Program Template;
3. exact Degree Level;
4. University-wide.

Effective date range is respected. If more than one current policy matches at the same highest specificity, processing is blocked until the Academic Policy conflict is resolved.

### Admission revocation
An unpaid/unadjusted demand created from an Admission Confirmation is automatically cancelled when that confirmation is revoked. If any demand has payment or adjustment activity, Admission Confirmation revocation is blocked until the financial activity is reversed/settled through the future Payment/Adjustment workflow.

### Demand provenance
`fee_demands.generation_mode` records `ADMISSION_AUTO`, `MANUAL_RECOVERY`, or future `BULK_PERIOD` so automatic, recovery and bulk generation remain auditable.
