# ADR 019 — Structured Admission Tie-breakers and Qualifying Thresholds

Status: IMPLEMENTED — OWNER QA REQUIRED
Date: 2026-08-26

## Context
The first Selection Rule milestone stored one generic minimum qualifying score and free-text tie-break instructions. That is readable by administrators but is not sufficient for deterministic automated Merit / Roster generation.

## Decision
Admission Selection Rules use explicit mode-aware qualifying thresholds and ordered child tie-break records.

### Thresholds
All admission selection scores consumed by the rule engine are normalized to 0-100.

- `minimum_merit_score`
- `minimum_entrance_score`
- `minimum_final_score` for COMBINED mode

The earlier `minimum_qualifying_score` column is retained only for compatibility/backfill and is not used by new writes.

### Tie-breakers
Executable tie-break logic is stored in `college_admission_selection_rule_tiebreakers` with explicit priority, criterion, comparison direction and optional criterion reference.

Free-text `tie_breaker_rules` remains human-readable policy wording only. No future ranking implementation may parse it as executable logic.

## Activation Rule
An INACTIVE Selection Rule may be drafted without structured tie-breakers, but it cannot become ACTIVE until at least one structured tie-breaker exists.

## Why
- deterministic merit list generation;
- auditable exact rule version and tie-break order;
- avoids ambiguous natural-language parsing;
- supports different University/College policies without hard-coding one fixed sequence;
- prevents redesign when Student Admission and Merit List execution are implemented.

## Future Consumption
Student Admission / Merit generation must calculate the primary normalized score from the Selection Rule, apply configured qualifying thresholds, then evaluate tie-break children in ascending `priority` order.
