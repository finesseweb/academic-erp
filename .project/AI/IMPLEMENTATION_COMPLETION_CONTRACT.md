# Implementation Completion Contract

A new persistent ERP module/table is NOT complete until all applicable items are delivered together:

1. schema/model/service/controller/UI
2. permissions and protected-role synchronization where required
3. audit events
4. individual Test Data Cleanup
5. Full Academic Test Reset dependency order
6. downstream dependency blockers
7. PAGE_SPEC / TABLE_SPEC / ADR
8. Current Implementation State / Changelog
9. frontend/backend validation parity
10. historical-reference rules for later transactional data

Cleanup remains explicit child-first. Do not globally disable foreign keys and do not use blanket TRUNCATE for ERP domain data.

Every future Admission, Student, Fee, Faculty, Attendance, Examination, Result, Certificate or other persistent module must extend the cleanup graph in the same implementation milestone.

## Delta Delivery / Shared-File Merge Rule

For milestone ZIP delivery:

- include only added or modified files, preserving exact project-relative paths;
- unchanged files must not be included;
- the user's original project remains the immutable baseline reference;
- `.project` remains the architecture/hierarchy authority;
- if a shared file (for example `routes/web.php`, sidebar, cleanup service, permission registry, or common UI component) was already modified by an earlier milestone, any later delta must be produced from the latest accumulated milestone state;
- never copy an older baseline version of a shared file into a later delta;
- before packaging, verify that prior milestone routes/features present in every changed shared file are still present.

### Schema Field Verification

Before adding eager-load field lists, query projections, DTOs, or frontend types for an existing table, verify the exact field names against the current migration and `.project/AI/DATABASE/TABLE_SPECS` documentation. Do not infer names such as `version_no` when the canonical schema uses a different field such as `version`.
