# ADR 186 — Restore Canonical Test Data Cleanup UI + Gateway Test Orders

## Problem
ADR185 exposed Gateway Test Orders by replacing the Test Data Cleanup page with an older UI baseline. This regressed the current cleanup design and removed newer cleanup menus.

## Decision
Restore the latest established Test Data Cleanup page structure (including Finance, Admissions, Access and Academic cleanup menus) and make only the additive change required for ADR184: add `Gateway Test Orders` as a normal cleanup entity.

## Gateway Test Orders cleanup
- Source: `online_payment_transactions`
- Scope: only rows with `purpose = CREDENTIAL_TEST_ORDER`
- Supports individual cleanup and the existing bulk cleanup mechanism.
- Full Academic Reset already deletes online gateway transactions before finance dependencies.
- Audit event: `TEST_GATEWAY_ORDER_CLEANED`.

## Preserved configuration
Cleanup must NOT delete:
- `college_payment_gateways` credential profiles
- encrypted gateway credentials/secrets
- Fee Head gateway routing / mappings
- provider configuration

These are configuration/master data, not transactional QA data.

## Regression rule
Future Test Data Cleanup additions must be additive against the current page and service baseline. Never replace the cleanup UI with an older snapshot to expose a new cleanup entity.
