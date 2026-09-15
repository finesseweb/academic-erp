# ADR 157 — Student Benefit parent row keeps Programme only

## Status
Accepted

## Context
ADR 156 groups all benefits belonging to the same admission/application into one parent row. The parent row was still showing billing-period and demand-count text (for example `Admission Initial · 1 demand`). Those details duplicate information already available inside the expanded Benefits child table and make the compact register unnecessarily long.

## Decision
- The Student Benefit Register parent row is a compact admission-level summary.
- The `Programme / Periods` column is renamed to `Programme`.
- The parent row shows only the programme name.
- Billing period, Fee Demand number, assignment source, scheme, amount and status remain in the expanded Benefits child rows.
- Grouping remains admission/application based as defined by ADR 156; this ADR changes presentation only.
- Individual and bulk-created benefits are treated identically.

## Rationale
High-volume operational registers must avoid repeating detail that is already available after expansion. The parent row should support quick scanning; transaction-level information belongs in the child detail.

## Data / Migration
No schema or data migration.
