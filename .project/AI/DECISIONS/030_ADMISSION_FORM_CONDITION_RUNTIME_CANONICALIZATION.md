# ADR 030 — Admission Form Condition Runtime Canonicalization

## Status
Accepted — 2026-08-27

## Decision
Dynamic admission-form answer conditions must compare canonical choice values rather than raw display-label casing. Form-builder option labels are presentation data while option values are persisted response data. Conditions authored with labels such as `OBC`, `SC`, or `ST` therefore resolve to the corresponding stored values such as `obc`, `sc`, and `st`.

The rule is enforced consistently in:
- internal College Application Entry rendering;
- public application rendering;
- Laravel server-side applicability / required validation; and
- future condition creation for choice fields.

Existing condition rows are not invalidated or rewritten destructively; runtime canonicalization keeps them compatible. `ALL` and `ANY` condition-match modes must have identical semantics on the client and server.
