# ADR 024 — Admission Form Configuration and Internal Application Entry Engine

Date: 2026-08-27
Status: ACCEPTED / IMPLEMENTED_IN_PACKAGE / OWNER_QA_REQUIRED

## Decision
Before continuing from Interview QA into Merit / Roster Generation, implement a controlled Admission prerequisite branch that extends the existing `college_admission_applications` foundation instead of introducing a parallel candidate/admission model.

## Core rules
1. `college_admission_applications` and `college_admission_application_choices` remain the authoritative transactional foundation.
2. Core linked fields are protected relational data and are not converted into arbitrary dynamic fields: College, Admission Cycle, candidate identity, Program Offering context, Intake/seat bucket, Application Choice, status, and submission/lock state.
3. Dynamic templates add extra steps and fields such as text, number, date, dropdown, radio, checkbox, multi-select, file/image upload and Yes/No.
4. Dynamic fields render through the existing ERP theme/components. Configuration changes data/behavior, not the visual design system.
5. Template ownership may be UNIVERSITY or COLLEGE. A manager user may be assigned to a template.
6. Scope mapping follows inheritance/override. More-specific active mappings win: Admission Cycle > College Program Offering > Program Template > Degree > Degree Level > College/University default.
7. Application Fee configuration uses the same scoped inheritance idea. The resolved fee/rule is snapshotted on the application so later fee changes do not rewrite submitted/history records.
8. REGULAR admission continues the Selection Rule-driven chain and locks the exact active Selection Rule version on submit.
9. DIRECT admission shares the same Application/Application Choice foundation but does not require a Selection Rule and may bypass Score/Interview/Merit processing. It must remain traceable into later Seat Allocation, Admission Approval and Student Enrollment.
10. Future candidate self-service/public forms must reuse this same template/application foundation; Stage 1 is internal College/University staff entry.

## Hierarchy effect
This is an approved prerequisite branch, not a replacement of the frozen Admission hierarchy. After Stage 1 owner QA, return to the existing checkpoint: Interview Scheduling / Evaluation QA, then Merit / Roster Generation.

## RBAC alignment amendment (2026-08-27)
Admission Form Setup follows the ERP's existing Access Management hierarchy. No parallel Admission-specific College enable switch or role allow-list is authoritative.

Access rules:
1. `SUPER_ADMIN` receives the Admission Form permissions by default as the University-level system owner.
2. The Admission Form permissions are registered in the common `permissions` catalog and are College-delegable.
3. Any eligible custom role may receive those permissions through the existing Role → Permissions matrix.
4. College access is effective only when the user has an active role assignment scoped to that College and that role carries the required permission.
5. Menu visibility is a reflection of the same permission check; backend authorization independently enforces University/global permission or College-scoped permission.
6. `COLLEGE_ADMIN` is not granted Stage 1 permissions automatically by the Stage 1 alignment migration; University administrators explicitly delegate them through the normal role/permission workflow.

University-owned base configuration remains available at `/admin/admission-form-setup`. College-owned configuration remains at `/college/{college}/admission-form-setup` and appears automatically when the signed-in College user has the relevant delegated permission in that College scope.

Template governance (`UNIVERSITY_CONTROLLED`, `UNIVERSITY_BASE_COLLEGE_EXTENSION`, `COLLEGE_CONTROLLED`) remains a form/template behavior rule; it is not an access-control replacement.
