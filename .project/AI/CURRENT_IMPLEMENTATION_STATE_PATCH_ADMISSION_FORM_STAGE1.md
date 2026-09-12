# Current Implementation State Patch — Admission Form Stage 1

Date: 2026-08-27
Status: IMPLEMENTED_IN_PACKAGE / OWNER_QA_REQUIRED

- Approved prerequisite branch inserted before Merit / Roster continuation.
- Added Admission Form Setup with University/College ownership, manager assignment, University base + College extension inheritance, governance mode, Regular/Direct applicability, dynamic steps, dynamic fields/options, scoped mappings, and Application Fee rules.
- Existing `college_admission_applications` remains authoritative; no duplicate admission/candidate transaction model was created.
- Applications now snapshot resolved template and fee context and can store custom field/document responses.
- REGULAR keeps Selection Rule locking and current downstream Score/Interview behavior.
- DIRECT shares the same application/choice chain but can be entered without a Selection Rule so later direct seat/admission processing can consume it.
- Resume checkpoint after QA: Interview Scheduling / Evaluation QA -> Merit / Roster Generation.

## Test Data Cleanup coverage
Stage 1 is included in Test Data Cleanup. Form Templates and Application Fee Rules are individually cleanable with dependency guards, and Full Academic Test Reset removes Stage 1 mappings/rules/templates after dependent Applications are deleted.
