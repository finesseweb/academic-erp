# Current Implementation State Patch — Interview Date/Time + Role-Gated Evaluators

Interview Scheduling now uses ERP-consistent date/time selectors instead of browser-native datetime UI. Evaluator eligibility is controlled through the existing Role Permission Management matrix using `college_admission_interview.evaluate`. Granting this permission to an active College role (for example Faculty) makes active same-College staff assigned to that role selectable as evaluators. College Administrator remains explicitly eligible. Applicant and Student identities remain excluded.

The evaluator permission is separate from `college_admission_interview.manage`: a role can be eligible to serve as an evaluator without receiving full Interview scheduling/management authority.
