# 2026-08-31 — Route + migration reconciliation
- Fixed MySQL 1059 on the academic-preference UNIQUE index with explicit `caa_pref_application_uq`.
- Kept explicit short FK/index names for both new applicant academic-preference tables.
- Restored College Admission Form Setup update/delete routes for templates, steps, panels and fields.
- Restored mapping delete and mapping public-access PATCH routes used by Save Step Layout / Enable / Disable Applicant Portal.
- Reconciled controller methods with the restored route contract while retaining Applicant Registration settings.
- No parallel feature gate introduced; existing RBAC remains authoritative.
