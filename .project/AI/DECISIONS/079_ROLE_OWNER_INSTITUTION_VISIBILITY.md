# ADR 079 — Role Owner Institution Visibility

Status: ACCEPTED / IMPLEMENTED
Date: 2026-09-02

## Context
University Role Management currently shows whether a role is System/Custom and whether its owner scope is GLOBAL/UNIVERSITY/COLLEGE, but a College-owned custom role does not identify which College owns it. In a multi-college University this makes roles with the same business purpose ambiguous and weakens scope review/auditability.

## Decision
The University Role list will resolve canonical College role ownership from `roles.owner_scope_reference=college:<stable-id>` and display the owning College name/code in a dedicated `Owner / Institution` column.

The page will also support an owning-College filter. University-owned roles display `University`; global roles display `Global`.

Role ownership and user-role assignment scope remain separate concepts. A University-owned protected role template such as `COLLEGE_ADMIN` remains University-owned even when its user assignments are College-scoped.

College Role Management remains strictly scoped to the route College and therefore already has an unambiguous institution context; its backend ownership guard remains unchanged.

## Integrity Rules
1. Do not infer role ownership from creation provenance. `owner_scope_type`/`owner_scope_reference` remain authoritative for ownership, while `created_by_scope_type` independently governs University-management visibility.
2. College ownership is authoritative only when `owner_scope_type=COLLEGE` and `owner_scope_reference` is the canonical `college:<id>` value.
3. An unresolved College reference must be shown as unavailable with the raw canonical reference rather than relabeled as University/Global.
4. Filtering by College applies only to College-owned roles.
5. Canonical ownership still requires no change; however, the 2026-09-03 creator-visibility amendment adds explicit creation-provenance columns so University Role Management can exclude College-created roles while retaining University-created College-owned roles.

## Files
- `app/Http/Controllers/RoleController.php`
- `resources/js/pages/roles/index.tsx`
- `.project/AI/PAGE_SPECS/UNIVERSITY_ADMIN/ROLES_LIST_PAGE.md`

## 2026-09-03 Visibility Amendment
University Role Management is creator-hierarchy filtered: roles created by University administration remain visible/control-capable, including College-owned roles created on a College's behalf. Roles created by College administration remain visible only in that owning College context and are excluded from University Role Management.
