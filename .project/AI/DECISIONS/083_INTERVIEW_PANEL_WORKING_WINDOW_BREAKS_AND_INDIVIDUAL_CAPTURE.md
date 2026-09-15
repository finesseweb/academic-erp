# ADR 083 — Interview Panel Working Window, Break Exclusion and Individual Capture

Status: IMPLEMENTED
Date: 2026-09-02

## Context
Reusable Interview Panels introduced bulk candidate scheduling, but the first implementation generated sequential slots indefinitely from the start time. Operationally a panel works only for a defined number of hours on a given day and may pause for lunch, tea or other breaks. The bulk picker also displayed every evaluator/candidate as checkbox rows, which becomes noisy at scale. Admission staff also still need a one-candidate flow for one-off or offline interviews.

## Decision
A reusable Interview Panel now has a finite same-day working window:
- start date/time;
- candidate slot duration in minutes;
- panel working duration in hours, stored as `session_duration_minutes`;
- zero or more labelled break periods inside that working window.

Slot generation is capacity-bound. A slot must fit completely inside the panel working window and must not overlap any configured break. When the next slot would overlap a break, scheduling resumes from the break end. If all selected candidates cannot fit, the operation is rejected before any panel/interview rows are created.

Evaluator and candidate bulk selection is add/search based rather than rendering every available record as a pre-shown checkbox. Selected records remain visible and removable before save.

The existing candidate-level Interview Scheduling / Evaluation flow remains available even when the candidate has no reusable panel assignment. This is the authoritative path for one-off/offline/manual interviews. Panel-linked interviews continue to inherit and lock the reusable panel evaluator roster.

## Validation
- panel working duration must contain at least one complete candidate slot;
- panel end must remain on the same calendar day;
- every break must be completely inside the panel working window;
- break end must be after break start;
- breaks cannot overlap;
- candidate slots cannot cross a break or the panel end;
- bulk create is atomic: insufficient capacity creates nothing.

## Historical compatibility
The new `session_duration_minutes` column is nullable so already-created panel records remain valid historical records. Existing panel/interview data, locked Selection Rules, evaluations, email behavior and downstream Merit/Seat/Admission locks are preserved.
