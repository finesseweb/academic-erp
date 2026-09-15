# ADR 106 — University Fee Curriculum Selector Uses Current Approved Version Only

## Status
Accepted — 2026-09-05

## Decision
When a University Fee Structure requires Curriculum scope, the selectable Curriculum list must contain only the **current approved version** for the matching University/Program Template/Academic Session.

Current Curriculum is derived by the existing Curriculum amendment contract:

- `lifecycle_status = ACTIVE`
- `approval_status = APPROVED`
- no approved successor/amendment exists

The Fee module must not introduce a second `is_current` flag and must not expose Draft, inactive, rejected, retired, previous, or superseded Curriculum versions for new University Fee Structures.

## Why
Fee billing periods are derived from actual Curriculum Terms. Allowing a superseded Curriculum would bind Fee configuration to obsolete term IDs and break consistency with Program Offering, Admission, progression, and future Fee Demand execution.

## Amendment behavior
An approved amendment becomes the current selectable Curriculum. The previous approved Curriculum remains historical and is not offered for new Fee Structure configuration.

An already ACTIVE historical Fee Structure is never silently rebound to a newer Curriculum. Its original `curriculum_id` remains auditable. A fee-policy change against the amended Curriculum must be explicit through deactivation/editing where allowed or a new Fee Structure/version.

## Validation
Backend validation mirrors the selector rule. A crafted request containing a previous/superseded Curriculum ID is rejected even if that Curriculum is still `ACTIVE + APPROVED` historically.
