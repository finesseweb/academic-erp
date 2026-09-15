# ADR 087 - Admission Setup Navigation and Permission Grouping

## Status
Accepted.

## Decision
Within **College Academic Setup**, the complete Regular Admission configuration/processing chain from **Merit / Roster / Selection Rules** through **Merit / Roster Generation** is grouped under one nested **Admission Setup** branch.

The branch contains, in workflow order:

1. Merit / Roster / Selection Rules
2. Admission Cycle
3. Admission Form Setup
4. Applications / Candidate Eligibility
5. Score Capture / Normalization
6. Interview Scheduling / Evaluation
7. Merit / Roster Generation

Program Offerings, Intake / Seat Capacity, and Reservation / Seat Distribution remain direct College Academic Setup items because they establish the academic/seat context consumed by Admission Setup.

## RBAC alignment
Role Permission Management must reflect the same functional boundary. All active permissions whose resource is one of the following are grouped under permission module **Admission Setup**:

- `college_admission_selection_rule`
- `college_admission_cycle`
- `college_admission_form`
- `college_admission_application`
- `college_admission_score`
- `college_admission_interview`
- `college_admission_merit`

No permission codes, sensitivity flags, delegability rules, role grants, or authorization checks are changed. This is a navigation and permission-module classification change only.

The existing College Role Permission screen groups permissions dynamically by `permissions.module`, therefore no separate Role Permission UI fork is introduced.

## Visibility rule
The Admission Setup branch is permission-filtered recursively. A user sees the branch only when at least one child is visible to that user, and sees only the child pages for which the user has the corresponding `*.view` permission.
