# Changelog Patch — Interview Date/Time + Role-Gated Evaluators

Date: 2026-09-02

- Replaced Interview browser-native datetime controls with the ERP project-style date selector plus explicit hour/minute/AM-PM controls.
- Applied the same explicit time-selector pattern to panel breaks.
- Added College-delegable `college_admission_interview.evaluate` permission to Role Permission Management.
- Evaluator search now includes only active same-College `COLLEGE_STAFF` whose active College role grants Interview Evaluator permission.
- Kept `COLLEGE_ADMIN` as an explicit evaluator eligibility exception.
- Backend panel and individual Interview services enforce the same role-permission rule as the UI.
- Applicant/Student evaluator exclusion, reusable panel logic, breaks, individual scheduling, email notifications and locked Selection Rule behavior are preserved.
