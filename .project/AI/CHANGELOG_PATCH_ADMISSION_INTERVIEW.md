# Changelog Patch — Admission Interview Scheduling / Evaluation

## 2026-08-27
- Implemented Interview Scheduling / Evaluation as the next Admission milestone after Score Capture.
- Added Interview and evaluator tables, models, permissions, controller/service/request, routes and College UI.
- Preserved exact submitted Application Choice + locked Selection Rule linkage.
- Added active College-user panel assignment and per-evaluator normalized scoring.
- Completed Interview updates the existing Admission Score context and re-runs qualification/final weighted score calculation.
- Integrated Interview child rows into Full Academic Test Reset before Score/Application deletion.
- Next milestone after owner QA: Merit / Roster Generation.
