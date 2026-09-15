# ADR 080 — Interview Evaluator Identity and Candidate Gate Integrity

Status: IMPLEMENTED
Date: 2026-09-02

## Context
Interview Scheduling already limits candidate/application choices to SUBMITTED + ELIGIBLE choices with Score Capture present and a locked Selection Rule whose Interview weight is greater than zero. However, the evaluator selector previously queried every ACTIVE user whose `primary_college_id` matched the College. Applicant accounts are also anchored to a College, so applicant registrations could incorrectly appear as panel evaluator options.

## Decision
Candidate eligibility and evaluator identity are separate gates.

### Candidate gate
The Interview Scheduling list must contain only Application Choices that are:
- application status `SUBMITTED`;
- choice eligibility status `ELIGIBLE`;
- backed by an existing Admission Score / Score Capture row; and
- locked to a Selection Rule with `interview_weight_percent > 0`.

The exact locked Selection Rule on the submitted choice remains authoritative. A newer/current rule must never be substituted.

### Evaluator gate
An interview evaluator must be:
- `users.account_type = COLLEGE_STAFF`;
- `users.status = ACTIVE`;
- `users.primary_college_id = current College`; and
- linked to at least one currently effective ACTIVE `user_roles` assignment with `scope_type = COLLEGE` and `scope_reference = college:<current-college-id>`, whose Role is also ACTIVE.

`APPLICANT`, `STUDENT`, University staff, other-College users, inactive users and College Staff without an active current-College role assignment must never be offered or accepted as evaluators.

The same evaluator gate is enforced in both page payload generation and backend save validation so direct requests cannot bypass the UI.

## UI
The evaluator dropdown displays the College Staff user's active College role name(s) with name/email so panel assignment is understandable. It is explicitly labelled/explained as a staff evaluator selector, not a candidate/student selector.

## Database
No schema migration is required.
