# ADR 176 — Late Fine Rule Lifecycle + Unused Rule Cleanup

Date: 2026-09-10
Status: IMPLEMENTED — QA PENDING

## Context
Late Fine / Penalty Rules already supported create, edit while INACTIVE, activate/deactivate and recalculation. QA exposed two usability/lifecycle gaps: rule actions were presented as large text buttons rather than the compact action pattern used elsewhere, and an unused QA/test Late Fine Rule had no safe removal path.

## Decision
1. Keep the existing Late Fine accounting and calculation contract unchanged.
2. Present row actions as compact icons: Edit, Activate/Deactivate, Delete unused rule.
3. Editing remains disabled while a rule is ACTIVE. The operator must deactivate first.
4. Deleting a rule is allowed only when the rule is INACTIVE and has no Late Fine Charge history.
5. Any historical Late Fine Charge — ACTIVE, SUPERSEDED or REVERSED — blocks rule deletion. QA charge history must be cleaned first through existing test-data cleanup coverage.
6. Deletion is audited as `FEE_LATE_FINE_RULE_DELETED` with the pre-delete rule snapshot.
7. Rule deletion uses the existing `college_fee_late_fine.manage` permission; no new RBAC permission is introduced.
8. Late Fine Register dates are rendered as human-readable dates instead of raw ISO timestamps.

## Safety / Accounting Invariants
- Gross Fee Demand is never changed by rule lifecycle actions.
- Fee Demand Items are never changed by rule lifecycle actions.
- Posted/paid Late Fine history is never silently removed with a rule.
- A rule with any calculation history is immutable for deletion and must remain auditable.
- Only explicitly selected unused INACTIVE rules can be deleted.

## Route
`DELETE /college/{college}/fee-late-fines/rules/{rule}`

## Migration
None.

## QA
1. Confirm Rules register shows compact Edit, Power and Trash actions.
2. ACTIVE rule: Edit and Delete must be disabled; Power must deactivate.
3. INACTIVE unused rule: Edit works; Delete asks confirmation and removes the rule.
4. Rule with any Late Fine Charge history: delete request must be rejected with a validation message telling the user to clean test charges first.
5. Activate/Deactivate existing behavior remains intact.
6. Due Date and As Of columns render readable dates (e.g. `05 Sep 2026`) rather than ISO timestamps.
