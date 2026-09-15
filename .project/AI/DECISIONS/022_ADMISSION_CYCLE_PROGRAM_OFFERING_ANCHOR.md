# ADR 022 — Admission Cycle Is Anchored to Program Offering

Status: IMPLEMENTED — OWNER QA REQUIRED
Date: 2026-08-26

## Context
A session-only Admission Cycle can exist even when the College has not actually activated a program offering. It also permits Applications to mix offerings under one cycle, which weakens the operational hierarchy already established by College Program Offering.

## Decision
`college_admission_cycles` belongs directly to one `college_program_offerings` row.

Authoritative chain:

`College -> ACTIVE Program Offering -> Admission Cycle -> Application -> ordered Seat Bucket/Specialization Choices -> locked Selection Rule version -> Score/Interview/Merit -> Seat Allocation -> Admission Confirmation -> Student Lifecycle`

Academic Session, Degree/Program and Curriculum context are inherited through Program Offering and must not be independently reselected by Admission Cycle.

## Activation
An Admission Cycle may be created INACTIVE for an ACTIVE Program Offering. Activation requires the same offering to remain ACTIVE, its Intake to be ACTIVE, and at least one eligible seat bucket to have an ACTIVE Selection Rule. Reservation remains optional at bucket level.

## Application Rule
One Application belongs to one Admission Cycle and therefore one Program Offering. Multiple preferences are allowed only among seat buckets/specializations within that same offering. A choice from another offering is invalid even if it belongs to the same Academic Session.

## Historical Safety
The exact Selection Rule version is still locked per submitted choice. Moving a live Admission Cycle to another offering is prohibited; deactivate first, and submitted/closed downstream history must remain traceable.
