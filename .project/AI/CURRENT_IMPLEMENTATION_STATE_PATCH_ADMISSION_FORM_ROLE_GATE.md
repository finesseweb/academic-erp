# SUPERSEDED — See CURRENT_IMPLEMENTATION_STATE_PATCH_ADMISSION_FORM_RBAC_ALIGNMENT.md

# Stage 1 Patch — College Role Gate for Admission Form Setup

Status: IMPLEMENTED / OWNER_QA_REQUIRED

University can now select one or more existing ERP roles per College when enabling Admission Form Setup.

Effective access requires:
- University feature gate ON for that College;
- logged-in user has an active `user_roles` assignment for one of the selected roles with `scope_type=COLLEGE` and `scope_reference=college:{id}`;
- role/user has the required Admission Form College permission;
- governance mode permits the attempted operation.

The College sidebar uses the same effective-role gate, while controllers independently enforce it to prevent direct URL/API bypass.

QA must verify both a selected role (allowed) and a non-selected role (denied) before Stage 1 can be accepted.
