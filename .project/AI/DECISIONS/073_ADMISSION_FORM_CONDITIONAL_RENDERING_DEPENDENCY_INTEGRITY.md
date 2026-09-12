# ADR 073 — Admission Form Conditional Rendering Dependency Integrity

## Status
Accepted — 2026-09-01

## Context
ADR 032 already establishes that any already-created eligible non-file field may be used as the parent/source of an Admission Form answer-based condition, regardless of Step order. The current implementation had regressed in three places: Add/Edit builder source lists still restricted some conditions to same/earlier Steps; Edit Field did not allow the saved answer condition to be changed/removed; and runtime academic-applicability filtering could leave a child condition pointing to a source field that was no longer part of the effective form.

## Decision
1. Answer-based condition source selection follows ADR 032: any existing eligible non-file field in the effective template hierarchy may be selected, irrespective of Step order.
2. FILE and IMAGE fields remain invalid condition sources.
3. Edit Field must expose the saved condition and allow changing its source, operator, values, or selecting `Always show` to remove it.
4. Editing an existing dependency must run cycle detection. A field may not depend on itself and a dependency chain may not loop back to the edited field.
5. Academic applicability is resolved before answer-condition evaluation. A source field outside the selected Admission Cycle/Offering academic context is treated as unavailable, not as a hidden stale value.
6. Runtime payload generation must not emit a conditional child whose required source field is absent from the effective form. Dependency pruning is recursive so no dangling condition chain reaches Applicant or Internal Application UI.
7. Backend validation must use the same effective applicability/condition semantics as both React runtimes.
8. Hidden or no-longer-applicable values remain removed during persistence as already defined by the Admission Form runtime contract.

## Scope
Applies to University Base Admission Form templates, College Extension templates, Applicant Portal application rendering, and College internal Application Entry.

## Database
No schema or migration change is required.
