# ADR 140 — Student Benefit Individual Active Duplicate Visibility

**Date:** 2026-09-08  
**Status:** Accepted / Implemented / QA Required

## Context
Student Benefits already prevented duplicate active assignments in the backend and Bulk Assignment excluded Fee Demands whose same scheme was PENDING or APPROVED. Individual Assignment, however, still rendered that scheme as apparently eligible until final submission. This created an inconsistent and misleading QA/user experience.

## Decision
For Individual Student Benefit assignment, the selected Fee Demand remains searchable because multiple different schemes may legitimately apply to one demand. The scheme resolver must additionally return any active assignment for each exact Fee Demand + Scheme pair.

A scheme with an existing `PENDING` or `APPROVED` benefit is shown in the Applicable Scheme dropdown but disabled and labelled:

`Already assigned — PENDING` or `Already assigned — APPROVED`.

The assignment button must not enable for such a scheme. `REJECTED` and `CANCELLED` records are historical and do not block a new assignment.

Backend duplicate validation remains mandatory and authoritative so stale tabs, concurrent requests, or client-side bypass cannot create duplicate active benefits.

## Consequences
- Individual and Bulk duplicate handling are consistent.
- A Fee Demand is not incorrectly hidden just because one scheme has already been assigned.
- Users can still apply another independently eligible benefit scheme to the same demand.
- Active financial/approval records cannot be duplicated accidentally.
