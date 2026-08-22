# Academic Approval — Workflow Setup

## Status
PHASE 5A IMPLEMENTED IN REPLACEMENT PACKAGE

## Purpose
Create a reusable, configuration-driven governance approval engine. No Dean, Director, Registrar, HoD, officer name or fixed approval chain is hard-coded.

## Current Phase 5A Scope
- Approval Workflow master
- Workflow code/name
- Applies To = Curriculum for the first integration
- Ordered Approval Levels / Stages
- Approver Role selection from existing Role Master
- Active / Inactive workflow
- Reject/Return remark policy fields
- Permission controls
- Audit events

## Role Source
Approvers are selected from existing Access Management -> Roles.
A workflow stores `approver_role_id`, not an officer name.

Example only:
Level 1 -> HoD role
Level 2 -> Dean role
Level 3 -> Registrar role

These are examples, never seeded as mandatory role names.

## Next Phase 5B
- bind a configured workflow to Curriculum approval
- Validate Structure before Submit
- DRAFT -> SUBMITTED / UNDER_APPROVAL
- freeze structural editing while submitted
- approver inbox
- Approve / Reject / Return
- comments/history
- final publish/activation only after final approval
- effective-date publishing

## Gate
Phase 5 is NOT PASS after 5A. It remains IN PROGRESS until Curriculum submission, decisions, history and activation are implemented and tested.
