# ADR 151 — Admission Revoke Finance Cascade + Installment Execution Contexts

Date: 2026-09-09
Status: Implemented / QA Pending

## Problem
Two integration gaps were found during Installment QA:

1. Revoking an Admission Confirmation must make its automatically-created Admission Initial Fee Demand non-operational. Historical finance rows must not be deleted, but the active demand, pending benefit work, and active installment schedules must no longer remain actionable.
2. Bulk Installment Scheduling incorrectly reused Fee Demand *generation* contexts. Admission Initial demand generation is automatic and intentionally excluded from routine bulk demand generation, so an automatically-created Admission Initial demand could contain an installment-enabled Fee Head yet never appear in `Load Eligible Students`.

## Decision
### Admission Confirmation revoke
- Revocation keeps immutable/auditable history; it does **not** physically delete a Fee Demand.
- Every non-cancelled Fee Demand linked to the revoked Admission is marked `CANCELLED` when there is no payment or approved adjustment activity.
- Active Installment Schedule rows under those demands are marked `CANCELLED` with actor/time/reason.
- PENDING Student Benefit assignments under those demands are marked `CANCELLED` with actor/time/reason.
- Approved adjustments/payments continue to block Admission revoke until their financial reversal/settlement workflow is completed.
- The operational Fee Demand Register defaults to non-cancelled demands. `Cancelled` remains available as an explicit historical filter.

### Bulk installment execution contexts
- Demand-generation contexts and installment-execution contexts are separate concepts.
- `bulk_contexts` remains the source for creating routine Fee Demands and continues to exclude Admission-purpose bulk generation.
- New `installment_contexts` are derived from **existing, active Fee Demands** containing `installment_allowed = true` Fee Demand Items.
- Therefore `ADMISSION_INITIAL / MIXED` is a valid installment execution scope even though it is not a routine demand-generation scope.
- Bulk Installment Preview/Apply accepts `ADMISSION_INITIAL` and `MIXED` and still enforces actual Fee Demand Item snapshot eligibility, active-demand status, no collection-started replacement, and net payable rules.
- The Bulk Installment UI has its own Installment Scope selector and no longer depends on the currently selected Fee Demand generation context.

## Financial invariants
- Revocation never deletes posted finance history.
- Gross Fee Demand and Fee Demand Items are not rewritten.
- Cancelled demand history remains auditable.
- A cancelled demand cannot receive/retain an active installment schedule.
- Admission Initial installment eligibility comes only from the Fee Demand Item snapshot (`installment_allowed`).

## QA
1. Confirm an Admission with an automatic Admission Initial demand and an installment-enabled Fee Head.
2. Before payment/approved adjustment, revoke Admission Confirmation.
3. Verify the demand disappears from the default active register and is visible under `Cancelled` with status `CANCELLED`.
4. Verify any active installment schedules and PENDING benefit assignments are cancelled.
5. Reconfirm/prepare a confirmed Admission Initial demand with an installment-enabled Fee Head.
6. Open Bulk Installment Schedule, choose `Admission Initial`, click `Load Eligible Students`, and verify the student loads.
7. Confirm cancelled/revoked Admission demands never load as eligible.
