# ADR 082 — Reusable Interview Panels, Bulk Candidate Assignment and Sequential Slots

Status: IMPLEMENTED
Date: 2026-09-02

## Context
The original Interview Scheduling screen stored `panel_name` directly on each candidate Interview. That was sufficient for one-candidate-at-a-time scheduling but caused the same real interview panel to be recreated manually for every candidate.

Admission operations need one real panel to interview many candidates while each candidate still owns a separate Interview result and score.

## Decision
Introduce a reusable College Interview Panel as the scheduling parent.

A panel stores:
- College;
- panel name;
- start date/time;
- slot duration in minutes;
- venue/mode;
- active/cancelled status;
- panel evaluator roster.

A panel may be assigned to many interview-ready Application Choices. Each selected candidate still receives a distinct `college_admission_interviews` row linked to the panel.

Candidate slot time is deterministic:

`panel start + ((slot sequence - 1) × slot duration)`

Example: Panel A starts 10:00 with 15-minute slots. Candidate slot 1 = 10:00, slot 2 = 10:15, slot 3 = 10:30.

## Candidate gate
Bulk assignment accepts only candidates who are:
- SUBMITTED;
- ELIGIBLE;
- already have Score Capture;
- locked to a Selection Rule with Interview weight > 0;
- not already scheduled for an Interview.

The exact locked Selection Rule remains authoritative.

## Evaluator integrity
Panel evaluators must be ACTIVE `COLLEGE_STAFF` users in the same College with a currently effective ACTIVE College role assignment. Applicant and Student identities cannot be evaluators.

For a panel-linked candidate Interview, the evaluator roster is controlled by the reusable panel and cannot be changed candidate-by-candidate. Raw score, maximum score and evaluator remarks remain candidate-specific.

## Email
When the panel is created, every assigned candidate receives an individual Interview Scheduled notification using central Laravel mail configuration. Each email contains that candidate's own generated slot.

Individual schedule changes trigger Rescheduled mail; cancellation triggers Cancelled mail. Email delivery failure is logged and does not roll back the academic scheduling transaction.

## Historical compatibility
Existing candidate Interviews remain valid with a nullable panel reference. Their stored `panel_name`, schedule, venue and evaluator snapshots remain authoritative historical data.

## Reason
This avoids creating a different logical panel for every student while preserving separate candidate evaluations, normalized scores, auditability and historical Selection Rule locking.
