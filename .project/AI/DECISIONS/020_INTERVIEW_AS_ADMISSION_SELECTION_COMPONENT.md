# ADR 020 — Interview as an Admission Selection Component

Status: IMPLEMENTED IN SELECTION RULE CONFIGURATION — FUTURE ADMISSION EXECUTION PRESERVED
Date: 2026-08-26

## Context
Some Colleges/Universities select candidates using Merit only, Entrance only, Interview only, or a weighted combination. Interview therefore cannot remain an informal note or be added later as unrelated Student Lifecycle logic.

## Decision
Interview is a first-class normalized 0-100 Admission Selection scoring component alongside Merit and Entrance.

The Selection Rule stores the policy/configuration only:
- Merit weight %
- Entrance weight %
- Interview weight %
- optional Minimum Merit Score
- optional Minimum Entrance Score
- optional Minimum Interview Score
- optional Minimum Final Weighted Score for COMBINED mode
- ordered structured tie-breakers, including Interview Score

Supported modes:
- MERIT: 100 / 0 / 0
- ENTRANCE: 0 / 100 / 0
- INTERVIEW: 0 / 0 / 100
- COMBINED: any two or all three components may be positive; total must equal exactly 100%

A minimum threshold may be configured only for a component whose weight is positive. All component/final scores are normalized to 0-100 before rule evaluation.

## Future Admission Processing Boundary
Selection Rule configuration does not schedule interviews or store candidate interview results.

When Student Admission Processing is implemented, preserve this flow:

Application / Candidate Eligibility
→ raw score capture
→ score normalization
→ Interview Scheduling / Panel Assignment when the ACTIVE Selection Rule has Interview weight > 0
→ Interview Evaluation / candidate normalized Interview score
→ Merit / Roster Generation from the exact ACTIVE Selection Rule version
→ structured tie-break evaluation
→ Seat Allocation / Reservation consumption
→ Admission Confirmation
→ Student Lifecycle

## Future Data Contract
The later Interview Scheduling / Evaluation implementation should preserve at least:
- candidate/application reference;
- Selection Rule version reference;
- interview schedule/session;
- panel/evaluator references;
- raw evaluator scores/remarks as required by policy;
- final raw interview score;
- normalized interview score 0-100 used by ranking;
- evaluation status and audit trail.

Merit/Roster generation must consume the stored normalized score and must not recalculate historical panel evaluation data differently after the Selection Rule version changes.

## Student Lifecycle Boundary
Student Lifecycle starts only after Admission Confirmation. It may retain read-only references to the admission/selection outcome, but it must not own Interview scheduling, evaluation, weighting, score normalization, merit calculation or tie-break logic.

## Reason
This keeps admission policy configurable, ranking deterministic, historical selection auditable, and future interview execution reusable across UG/PG/professional programs without redesigning Student records.
