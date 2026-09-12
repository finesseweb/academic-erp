# Changelog Patch — Admission Interview Panel Capacity / Breaks

Date: 2026-09-02

- Added finite panel working duration instead of unlimited sequential slot generation.
- Added multiple labelled break periods and guaranteed that generated candidate slots never overlap a break.
- Added atomic capacity validation; insufficient panel time creates no partial schedules.
- Changed bulk evaluator and candidate selection to search/add/remove UX instead of pre-shown checkbox lists.
- Restored/retained candidate-level individual scheduling for one-off/offline interview capture.
- Preserved locked Selection Rule behavior, evaluator identity rules, email notifications, score normalization and downstream locks.
- Added additive migration only; existing panel rows remain historical-compatible through nullable session duration.
