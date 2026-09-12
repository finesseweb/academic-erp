# Current Implementation State Patch — College Admission Form Setup Render Recovery

Date: 2026-08-31
Status: OWNER_QA_REQUIRED

The College Admission Form Setup blank-page regression is fixed.

- Controller now returns current approved ACTIVE Curriculum options using the existing amendment/successor rule.
- React Admission Form Setup page now has safe empty-array defaults for collection props.
- No deprecated `college_admission_form_access_controls` runtime dependency is reintroduced.
- RBAC remains the only access authority; `allow_college_override` remains structural governance only.

QA: open `/college/{college}/admission-form-setup`, confirm page renders, then open Add/Edit Dynamic Field and confirm Curriculum dropdown contains only current approved versions.
