# ADR 023 — Admission Score Normalization and Locked Rule Consumption

## Decision
Admission scoring is a downstream consumer of the exact Selection Rule version locked when an Application is submitted. It must never resolve a newer active rule during score capture.

## Components
Merit and Entrance raw marks are normalized to 0-100 in this milestone. Interview remains a first-class weighted component but is operationally captured later by Interview Scheduling / Evaluation, which will update the same score context and trigger re-evaluation.

## Why
- preserves historical traceability when Selection Rule V2/V3 is created later
- supports source exams with different maximum marks
- prevents duplicate scoring formulas in Merit/Interview modules
- keeps Student Lifecycle outside Admission selection logic

## Downstream contract
Merit / Roster Generation consumes only SUBMITTED + ELIGIBLE choices with a score result that is QUALIFIED. If Interview is required, `PENDING_INTERVIEW` is not merit-ready.

## Dynamic Merit source mapping (2026-09-01)
When Merit has a positive Selection Rule weight, an INACTIVE rule version may optionally map one or more Admission Form `NUMBER` field pairs as Merit sources. Each source stores stable field IDs for Obtained and Maximum marks plus an internal Merit-source weight. Source weights total exactly 100%.

If mapped sources exist, Score Capture must not ask staff to re-enter those marks. It reads the submitted application's locked dynamic field values, normalizes every pair to 0-100, calculates the weighted composite Merit score, and stores a source snapshot for audit/reproducibility. If no sources are mapped, the existing manual Merit capture remains valid for backward compatibility.

Mappings belong to the exact Selection Rule version. A later form/rule version must not silently alter applications already locked to an older rule version.
