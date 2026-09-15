# ADR 118 — Fee Demand Bulk Context Is Derived from Fee Setup and Academic Eligibility

## Status
Accepted — 2026-09-05

## Decision
Fee Demand does not maintain an independent Semester/Academic-Year configuration. Bulk demand contexts are derived from the effective University + College Fee Structures that already apply to the selected College Program Offering.

The Fee Setup remains authoritative for:
- purpose (`ADMISSION`, `ACADEMIC`, `EXAMINATION`, `OTHER`),
- collection basis (`ONE_TIME`, term-wise, academic-year-wise, specific term/year),
- exact billing period,
- amount,
- Mandatory,
- Enrollment Clearance Required,
- Installment Allowed.

University MANDATORY and adopted OPTIONAL structures plus ACTIVE College-local structures are resolved exactly as in Fee Foundation. Fee Demand snapshots these values and never redefines them.

## Admission-stage demand
Admission Confirmation automatically generates an initial demand from:
1. applicable `ADMISSION`-purpose charges; and
2. only first-period `ACADEMIC` charges explicitly marked `Enrollment Clearance Required`.

Admission Fee remains optional. `EXAMINATION` and `OTHER` charges are never pulled into Admission Confirmation merely because they happen to use period 1.

Manual recovery remains only for earlier/missed initial demands.

## Program Offering bulk demand
Routine bulk demand is generated from:
`Program Offering → effective Fee Setup → Purpose → Fee-derived Billing Context → eligible cohort → bulk demand snapshots`.

A term-wise Fee Structure exposes real Curriculum terms (Semester/Trimester/Term). An academic-year-wise Fee Structure exposes Academic Year groups supported by the exact Curriculum active terms. Specific-period and one-time structures expose only their configured billing context.

Collection-basis changes in Fee Setup therefore change future available demand contexts, but never rewrite an already-generated demand snapshot.

## Eligibility
The first Academic billing period has no previous academic progression. Until Student Enrollment exists, the confirmed-admission cohort is the safe authoritative bridge for first-period Academic bulk demand.

Second and later Academic periods must consume authoritative Student Enrollment + Academic Progression results. The applicable University Academic Policy is resolved automatically from the Program Offering with the existing precedence:
`Curriculum → Program Template → Degree Level → University`.

Fee Management must never calculate pass/fail/promotion from marks on its own.

`EXAMINATION` bulk demand is blocked until authoritative examination eligibility/registration exists. `OTHER` bulk demand is blocked until an explicit target-cohort rule exists. The system must not charge a whole Program Offering merely because a Fee Structure exists.

## Duplicate protection
A Fee Structure Item + exact source billing period may appear only once in non-cancelled demands for one admission. This prevents Admission-stage clearance charges from being charged again when the first Academic bulk demand is subsequently generated.

## UI
College Fee Demands exposes:
- Program Offering,
- Demand Purpose derived from applicable Fee Setup,
- Billing Period derived from Fee Setup/Curriculum,
- resolved University Academic Policy,
- current cohort/readiness reason,
- bulk generate action only when the eligibility source is authoritative.

No page-specific CSS or independent fee-period master is introduced.
