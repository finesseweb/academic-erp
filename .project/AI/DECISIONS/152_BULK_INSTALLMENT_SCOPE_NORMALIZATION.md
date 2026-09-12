# ADR 152 — Bulk Installment Scope Normalization

## Context
ADR 151 separated Bulk Installment execution from Fee Demand generation contexts and derived installment scopes from existing active Fee Demands. QA then exposed a regression: existing installment-enabled students, including older demands created before `demand_context` / `billing_basis_group` were introduced, could appear in the scope selector but fail to load as candidates.

The root cause was inconsistent matching. Scope discovery normalized missing legacy context values, while candidate loading compared the raw nullable Fee Demand context columns exactly.

## Decision
Bulk Installment scope discovery and candidate loading MUST use the same normalized context rules.

### Normalized purpose
1. Use `fee_demands.demand_context` when present.
2. `ADMISSION_AUTO` / `MANUAL_RECOVERY` demands normalize to `ADMISSION_INITIAL`.
3. Otherwise derive from the Fee Demand Item purpose:
   - ADMISSION / ADMISSION_INITIAL -> ADMISSION_INITIAL
   - ACADEMIC -> ACADEMIC
   - EXAMINATION -> EXAMINATION
   - everything else -> OTHER

### Normalized billing basis
1. ADMISSION_INITIAL always uses `MIXED` because an Admission Initial demand can contain admission-stage and first-period academic clearance items together.
2. Use `fee_demands.billing_basis_group` when present.
3. Otherwise derive from Fee Demand Item charge basis:
   - PER_TERM / SPECIFIC_TERM -> TERM
   - PER_ACADEMIC_YEAR / SPECIFIC_ACADEMIC_YEAR -> ACADEMIC_YEAR
   - otherwise -> ONE_TIME

### Normalized period
Use `fee_demands.billing_period_no`, else `fee_demand_items.source_period_no`, else 1.

## Result
- Existing pre-context Fee Demands remain bulk-installment eligible.
- Newly auto-generated Admission Initial demands load correctly when an item is installment-enabled.
- Cancelled demands remain excluded.
- Collection-started demands remain visible as blocked and cannot be replaced.
- Existing active installment schedules remain eligible for replacement only before collection starts.
- No finance history is deleted or rewritten.

## Migration
No migration required.
