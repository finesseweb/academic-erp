# ADR 181 — Gateway Credential Profile Migration MySQL FK Index Fix

## Status
Accepted — 10 Sep 2026

## Context
ADR 180 removes the original unique index `cpg_college_provider_uq` so a college can configure multiple credential profiles for the same payment provider. On MySQL, that composite index was also being used to support the `college_id` foreign key. Dropping it therefore failed with MySQL error 1553: the index was needed by a foreign-key constraint.

## Decision
Before dropping `cpg_college_provider_uq`, create a dedicated `college_id` index named `cpg_college_fk_idx`. Then remove the old uniqueness constraint and add the non-unique lookup index `cpg_profile_lookup_idx` on `(college_id, provider, environment)`.

The migration is defensive/idempotent at the index-operation level so a retry after a partially applied DDL attempt is safe. The rollback restores the old unique index before removing the temporary FK-support index.

## Functional impact
No gateway behavior changes. This only makes the ADR 180 schema transition MySQL-safe while preserving all foreign keys and allowing multiple credential profiles per provider.
