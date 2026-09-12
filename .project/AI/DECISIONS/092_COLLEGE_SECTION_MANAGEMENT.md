# ADR 092 — College Section Management

## Status
Accepted for implementation — 2026-09-04

## Context
Batch Management is Owner-QA accepted. The frozen College Academic Setup sequence requires Section Management before College Academic Calendar and Student Enrollment / Lifecycle.

## Decision
A Section is an operational child of exactly one Batch:

`College -> Program Offering -> Intake/Reservation context -> Batch -> Section -> Student Enrollment`

Section stores only its Batch FK, code, name, status and notes. It does not duplicate Program, Curriculum, Academic Session, Intake, Reservation or Admission capacity.

New Sections start INACTIVE. Creation and activation require an ACTIVE College, ACTIVE Batch, ACTIVE parent Program Offering and ACTIVE parent Intake. Section activation never consumes or creates admission seats.

Changing the parent Batch is blocked once the Section is ACTIVE or has Student Enrollment references. Future active Student Enrollment rows block Section deactivation.

Section codes are unique within a Batch. The same code may exist in a different Batch.

## UI Representation
Section Management uses the Batch as the visual parent. Program, Academic Session, Curriculum and approved Intake are displayed once in the Batch context, with Sections nested below it. The parent Intake value must be labelled as shared Program Intake context and must not look like per-Section capacity. `Add Section` belongs inside the eligible Batch card and fixes that Batch for creation. For UI consistency, the Section disclosure must reuse the existing Academic Structure expandable-row pattern (Chevron + label + summary) rather than introducing a separate accordion interaction. This is a presentation rule only; the underlying hierarchy and capacity authority remain unchanged.

## Security
College-scoped RBAC:
- `college_section.view`
- `college_section.create`
- `college_section.update`
- `college_section.enable`
- `college_section.disable`

All backend authorization uses exact College scope. UI visibility is not an authorization boundary.

## Audit
Create, update, activate and deactivate actions are audited as College-scoped events.

## Dependency Protection
An ACTIVE Section blocks parent Batch deactivation. Backend protection remains authoritative; UI should disable the parent action when the dependency is already known.

## Test Data Cleanup
Sections are cleanup-aware. Individual Section cleanup is blocked by future Student Enrollment references. Full Academic Reset removes Sections before Batches.

## Consequence / Next Milestone
Owner QA -> College Academic Calendar -> Student Enrollment / Lifecycle.
