# ADR 028 — Admission Form College Operational Mapping

## Status
Accepted — Stage 1 prerequisite branch.

## Decision
University owns the reusable Admission Form definition and governance. Affiliated Colleges own the operational mapping that decides which ACTIVE form is used for a College Program Offering and Admission Cycle.

`Allow College Override` controls only whether the College may create/modify a College extension of the University Base. It does **not** control whether an authorized College may map and use an ACTIVE University Base form.

College mapping requires the existing `college_admission_form.map` RBAC permission and is backend College-scoped. Every mapping created from an affiliated College context stores that `college_id`, even when the selected template is University-owned.

The mapping UI asks for Program Offering + Admission Cycle only. Degree Level, Degree and Program are derived from the selected Program Offering to avoid duplicate/manual hierarchy entry. The Admission Cycle must belong to the same Program Offering.

For the same College + Program Offering + Admission Cycle, the newly saved active mapping supersedes the previous active mapping. Removing a mapping marks it INACTIVE instead of deleting history.

## Why
Program Offering is a College operational entity. Requiring the University to map every affiliated College offering would not scale and would mix University governance with College operations. Keeping definition at University scope and usage mapping at College scope preserves the existing project hierarchy and makes the future Application Entry resolver deterministic.

## Resume point
This remains inside Stage 1 QA. After Stage 1 acceptance, resume Interview QA, then Merit / Roster Generation as already frozen.
