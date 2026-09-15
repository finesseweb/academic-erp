# ADR 148 — Test Data Cleanup Bulk Selection / Module Cleanup

**Date:** 2026-09-09  
**Status:** Accepted / Implemented / QA Pending

## Context
Test Data Cleanup originally required operators to clean test records one at a time. As Admission, Fees and Academic Setup QA data grows, this makes repeated QA reset work unnecessarily slow and increases the chance of leaving partial test data behind.

## Decision
Test Data Cleanup uses a consistent bulk-selection pattern across module tabs:

1. Every cleanable record has a checkbox.
2. Blocked/protected records remain visible but cannot be selected.
3. The table header provides **Select all cleanable records in the current view**.
4. **Clean Selected (N)** removes only checked records.
5. **Clean All Cleanable (N)** cleans the complete module, independent of an optional UI filter such as Applicant Program Offering.
6. Bulk actions use one explicit module confirmation phrase: `CLEAN-<MODULE>-TEST-DATA`.
7. Existing dependency guards remain authoritative. Bulk cleanup never bypasses blocked/protected rows.
8. Curriculum bulk cleanup retains Curriculum downstream-reference checks.
9. Academic Policy bulk cleanup remains version-chain-aware. If multiple selected policy versions belong to the same chain, the chain is cleaned only once; later missing versions are skipped safely.
10. Existing individual Clean / Reset Approval / Deactivate for Testing actions remain available for exceptions and targeted QA.

## Safety / Audit
- `test_data_cleanup.manage` remains mandatory.
- Environment-level Test Data Cleanup enablement remains mandatory through the existing service guards.
- Individual cleanup services remain authoritative, so existing audit events and child-first/domain-specific cleanup logic are preserved.
- Records blocked by financial, admission, student-lifecycle or other operational references are preserved.
- `Clean All Cleanable` means all currently cleanable records in that module, **not** a raw table truncate.

## UI consistency rule
Any future Test Data Cleanup module that lists multiple records must support the same checkbox + Select All + Clean Selected + Clean All Cleanable pattern unless the module has a documented safety reason requiring a different workflow.

## Migration
No database migration is required.
