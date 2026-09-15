# ADR 050 — Effective Curriculum Scope Across Admission and Seat Workflows

Date: 2026-08-31
Status: Accepted

## Rule

A Program Template Discipline/Specialization being configured does not by itself make it operational for a current Program Offering.

For consumption workflows, a scope is effective only when the Program Offering's current Curriculum has at least one ACTIVE Course / Paper mapping for that scope, through an ACTIVE term and slot, and the Course is ACTIVE.

## Authoring vs Consumption

Authoring screens still expose Program Template specializations so the first specialization-specific curriculum paper can be mapped.

Consumption screens use only effective current Curriculum scope:

- Public Applicant Academic Selection
- College Admin Add Admission Application
- Intake / Seat Capacity allocation
- Reservation / Seat Distribution buckets
- Selection Rule / Merit-Roster bucket selection
- Applicant-to-Student enrollment handoff validation

## Discipline

A Discipline appears downstream only if at least one active Curriculum Course Mapping has that `discipline_id`.

## Specialization

A Specialization appears downstream only if at least one active Curriculum Course Mapping has both its `discipline_id` and `specialization_id`.

If a Specialization exists on the Program Template but has no current Curriculum paper, it remains available for curriculum authoring but is hidden from Admission and Seat workflows.

## Intake / Reservation

Unused Discipline/Specialization scopes cannot be newly allocated in Seat Intake.
Existing stale allocations remain available for cleanup, but Intake activation rejects them.
Reservation and Selection Rule bucket discovery excludes stale scopes.

## Student Handoff

Before an applicant-owned submitted application is promoted to Student access, its stored Discipline/Specialization is revalidated against the effective current Program Offering Curriculum.

## Seat Independence

This rule controls which academic scopes are valid for seat configuration. It does not cap application submission. Applicant count remains independent from intake capacity; seat allocation occurs later.
