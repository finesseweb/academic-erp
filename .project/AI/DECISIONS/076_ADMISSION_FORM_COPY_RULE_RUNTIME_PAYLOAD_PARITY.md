# ADR 076 — Admission Form Copy Rule Runtime Payload Parity

## Status
Accepted

## Decision
Admission Form advanced field rules configured in the builder must be delivered to every effective runtime form payload used by both the Public Applicant form and College **Add Application / Edit Draft**.

The effective payload must include active `copy_rule` metadata (source field, trigger field, trigger values, read-only flag) and active `comparison_rule` metadata. Runtime academic applicability remains authoritative. If applicability removes a copy source/trigger or comparison source, the target field remains available but the dangling advanced rule is omitted for that effective context.

## Required runtime behavior
For a target field configured with a copy rule:
1. Evaluate the trigger field against configured trigger values using canonical value matching.
2. When active, copy the current source value into the target.
3. Continue synchronizing when the source changes while the trigger remains active.
4. If `read_only` is enabled, lock the target control while the rule is active.
5. When the trigger becomes inactive, stop synchronization and restore manual editing.
6. The backend remains authoritative and reapplies active copy rules before validation/persistence, so a disabled/locked browser control cannot bypass or lose the copied value.

This behavior must be identical in Public Applicant rendering and College Add Application / Edit Draft rendering.

## Reason
The builder and backend already stored and enforced copy rules, but the effective form resolver did not serialize/eager-load those rules. Therefore runtime UIs received `copy_rule = null/undefined` and could not execute configured copy behavior.
