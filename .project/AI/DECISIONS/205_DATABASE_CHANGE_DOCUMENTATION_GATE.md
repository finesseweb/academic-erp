# ADR 205 — Database Change Documentation Gate

Status: ACCEPTED
Date: 2026-09-17

## Decision
Every Academic ERP change that affects database state or database structure must update the Project OS database-impact documentation in the same patch. Updating only an ADR, changelog, or implementation-state file is not sufficient.

This gate applies to:
- new/changed/dropped tables or columns;
- foreign keys, indexes, unique constraints and other constraints;
- reference-data, permission/RBAC, seed or lifecycle-data migrations even when no new domain table is created;
- migrations introduced only for corrective/recovery purposes.

The DB documentation must state, as applicable:
1. migration filename and classification;
2. authoritative domain ownership and relationships;
3. columns/tables affected;
4. FK/index/constraint behavior and explicit names where naming limits/recovery matter;
5. whether a new domain table is created;
6. partial-DDL/restart-safety behavior when a migration can fail after earlier DDL commits;
7. QA status and any recovery instruction.

## Closure gate
A milestone cannot be marked Owner QA Passed / Closed until documentation completeness is checked for: DB impact, ADRs, current implementation state, changelog, and RBAC/audit documentation when those areas are affected.

## Reason
Project OS must remain a reliable description of the running system. Schema changes that exist only in migration code are considered incomplete project work.
