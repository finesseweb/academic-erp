# ADR 084 — Project Date/Time Picker and Role-Gated Interview Evaluator Eligibility

Date: 2026-09-02
Status: Accepted

## Context
Interview Scheduling was still using browser-native `datetime-local` / time inputs while the Academic ERP already has its own reusable project-style `DatePicker`. Evaluator eligibility was also too broad: any active College Staff member with any active College role could appear. Operationally the College must decide, through Role Management, which staff categories may serve on admission interview panels. For example, granting evaluator eligibility to the Faculty role should make the active Faculty users of that College selectable without exposing unrelated College Staff.

## Decision
Interview Scheduling uses a project-consistent date/time UI composed from the ERP `DatePicker` plus explicit hour, minute and AM/PM controls. The same explicit time-selector pattern is used for Interview break start/end times. Backend payloads remain the existing `Y-m-dTH:i` / `H:i` formats, so no Interview scheduling schema change is required for the picker.

A dedicated College-delegable permission is introduced:

`college_admission_interview.evaluate`

A user is eligible to be added as an Interview evaluator only when all of the following are true:
- identity is `COLLEGE_STAFF`;
- `primary_college_id` is the current College;
- user is ACTIVE;
- the user has a currently effective ACTIVE College-scoped role assignment for the current College;
- at least one such active role grants `college_admission_interview.evaluate`.

`COLLEGE_ADMIN` is an explicit exception to the permission gate while its active College-scoped assignment is effective. Applicant and Student identities remain invalid regardless of any accidental data relationship.

## Role Management behavior
The new permission is registered in the existing Admission permission matrix and is College-delegable. No separate evaluator master is introduced. Therefore:
- grant `college_admission_interview.evaluate` to a custom role such as Faculty when that staff category is allowed to sit on interview panels;
- every active College Staff user carrying that active role in that College becomes available in the evaluator search;
- removing/deactivating the role/assignment removes that user from future evaluator selection;
- College Administrator remains eligible as the operational exception.

The permission controls evaluator **eligibility** only. Existing scheduling/manage authorization remains governed by `college_admission_interview.manage`.

## Integrity
The same eligibility rule is enforced both in the controller payload and in panel/individual Interview backend services. A direct HTTP request cannot add a user who is absent from the UI eligibility set.
