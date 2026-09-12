# Current Implementation State Patch — Admission Form Permission Completion

Date: 2026-08-27

Stage 1 Admission Form Setup now follows the same action-level RBAC pattern used by established ERP modules such as Academic Calendar.

Active implemented permissions:
- `college_admission_form.view`
- `college_admission_form.create`
- `college_admission_form.update`
- `college_admission_form.status`
- `college_admission_form.step_create`
- `college_admission_form.field_create`
- `college_admission_form.map`
- `college_application_fee.manage`

Rules:
- `SUPER_ADMIN` receives all implemented permissions by default.
- College/custom roles receive permissions only through the existing Roles → Permissions workflow and College scope enforcement.
- The former broad `college_admission_form.manage` capability is superseded by action-level permissions and is marked INACTIVE after its role assignments are migrated to the equivalent granular capabilities.
- Controller authorization and frontend action visibility both use the granular permission appropriate to the action.

Stage 1 remains OWNER_QA_REQUIRED. After Stage 1 QA, resume Interview QA and then Merit / Roster Generation according to `NEXT_WORKFLOW.md`.
