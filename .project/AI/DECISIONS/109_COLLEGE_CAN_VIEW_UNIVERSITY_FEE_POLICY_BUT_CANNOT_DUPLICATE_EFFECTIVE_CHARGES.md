# ADR 109 — College can view University fee policy but cannot duplicate effective charges

## Status
Accepted — 2026-09-05

## Decision
A University-owned Fee Structure that is applicable to a College remains authoritative and read-only. College Fee Management must provide a **View Structure** action before and after OPTIONAL adoption, and for MANDATORY structures. The read-only view exposes configured total, Billing Periods, Fee Heads, period amounts, Mandatory, Enrollment Clearance, Installment and status. It exposes no University mutation actions.

A College may still create its own Fee Structure for the same Program Offering to add genuinely local charges. However, once a University structure is effective for the College (MANDATORY automatically, or OPTIONAL with ADOPTED status), the College must not configure the **same Fee Head over overlapping academic billing coverage for the same fee purpose**. This prevents accidental double charging while preserving legitimate local supplements.

Billing overlap is compared by underlying academic term coverage so a University Academic-Year charge also conflicts with a College term charge when both cover the same underlying Curriculum term. ONE_TIME overlaps ONE_TIME within the same purpose.

OPTIONAL University structures that are not adopted do not block College-local configuration. Stopping use of an OPTIONAL structure removes that University structure from the effective overlap guard.
