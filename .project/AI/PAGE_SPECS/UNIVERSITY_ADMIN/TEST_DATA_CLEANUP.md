# Test Data Cleanup

## Status
IMPLEMENTED_IN_REPLACEMENT_PACKAGE

## Purpose
Development/testing convenience only.

This is NOT a generic SQL/database wipe screen. It is a controlled cleanup tool for known ERP test entities so foreign keys, approval history and future operational references are handled safely.

## Permission
Single sensitive permission:
`test_data_cleanup.manage`

When a Role has this permission, sidebar shows:
`System Maintenance -> Test Data Cleanup`

The permission is:
- sensitive
- not college-delegable
- granted to SUPER_ADMIN by migration
- assignable to another trusted testing role if required

## Environment Safety
Cleanup execution is enabled automatically in local/testing/development/staging environments.

Production requires explicit opt-in:
`TEST_DATA_CLEANUP_ENABLED=true`

Without that flag, the page may be visible to an authorized user but destructive execution remains disabled.

## Curriculum Test Cleanup — Current Scope
The first cleanup tool removes one selected Curriculum test record and its owned/testing hierarchy:

- approval_request_stages for that Curriculum
- approval_requests for that Curriculum
- curriculum_course_mappings
- curriculum_slots
- curriculum_terms
- curriculum
- old test audit rows tied to the removed Curriculum/request/term/slot resources

It does NOT delete reusable masters:
- Course / Subject Master
- Program Template
- Academic Session
- Degree/Discipline
- Users
- Roles/Permissions
- Approval Workflow configuration

A final audit row `TEST_CURRICULUM_DATA_CLEANED` is retained.

## Safety Checks
Cleanup is blocked if known downstream operational tables reference the Curriculum.

The service checks only tables/columns that actually exist, so this guard grows safely as later modules are implemented.

Examples:
- Course Offering
- Curriculum Assignment
- Student Enrollment
- Admissions

## Confirmation
User must type the exact Curriculum Code before cleanup.

No lifecycle exception is needed:
DRAFT, ACTIVE, RETIRED, SUBMITTED or APPROVED test Curriculum may be cleaned through this special tool when operational references do not exist.

Normal Curriculum Delete rules remain unchanged.

## Future Extension
As Admissions, Student, Fees, Faculty and Examination modules are built, add separate cleanup sections only through documented dependency-aware cleanup services. Never add arbitrary table truncate/delete access to this UI.

## Academic Policy approval cleanup dependency

When test Academic Policies are eligible for cleanup, dependency-aware cleanup must include:
1. `approval_request_stages` whose parent request has `subject_type = ACADEMIC_POLICY`;
2. `approval_requests` for the target policy IDs;
3. progression source-term mappings;
4. progression rule sets;
5. grade bands / grading rule;
6. assessment/examination rule;
7. attendance rule;
8. credit category requirements;
9. credit/completion rule;
10. policy amendment/version children only when the requested cleanup scope explicitly includes the full version chain.

Never delete one approved historical policy version while retained operational records reference it.

## Academic Policy Test Data — IMPLEMENTED

The Test Data Cleanup Center includes an **Academic Policies** tab using the same existing maintenance UI pattern.

Each Academic Policy row shows:
- Policy name/code/version/scope.
- Lifecycle + approval status.
- Number of versions in the linked amendment chain.
- Approval request count.
- Counts for Credit / Completion, Attendance, Assessment / Examination, Grading and Promotion / Progression child configuration.
- Cleanup safety state.

### Reset Approval

For a standalone Academic Policy test record with no downstream operational reference:
1. delete `approval_request_stages`;
2. delete `approval_requests` where `subject_type = ACADEMIC_POLICY`;
3. reset Policy to `DRAFT / NOT_SUBMITTED`;
4. clear Current/version activation state;
5. clear validation checkpoint;
6. preserve configured policy rule sections.

Approval reset is intentionally blocked when the selected policy belongs to a multi-version amendment chain. This prevents a partial reset from corrupting Current/Previous version semantics.

### Clean Academic Policy / Clean Chain

Academic Policy cleanup is **version-chain aware**.

Selecting Clean on any version resolves the complete linked Policy chain through `parent_policy_id` and removes the complete test chain only when no downstream operational records reference any version.

Dependency-safe deletion order:

```text
approval_request_stages
→ approval_requests (subject_type = ACADEMIC_POLICY)
→ academic_policy_progression_rule_terms
→ academic_policy_progression_rule_sets
→ legacy academic_policy_progression_rules when present
→ academic_policy_grade_bands
→ academic_policy_grading_rules
→ academic_policy_assessment_exam_rules
→ academic_policy_attendance_rules
→ academic_policy_credit_category_requirements
→ academic_policy_credit_completion_rules
→ clear self version links
→ academic_policies version chain
```

### Safety

Cleanup checks known operational tables only when the table/column exists, including future/result-oriented `academic_policy_id` references.

If any operational reference exists:
- Reset Approval is blocked.
- Clean / Clean Chain is blocked.
- UI reports that downstream operational references exist.

This remains a test/development cleanup utility and never acts as an unrestricted database delete console.

## Academic Policy cleanup route contract

Academic Policy cleanup uses dedicated routes and must NOT fall through to the generic master-cleanup route.

- DELETE `/admin/system-maintenance/test-data-cleanup/academic-policies/{academicPolicy}` → complete standalone policy or version-chain cleanup.
- POST `/admin/system-maintenance/test-data-cleanup/academic-policies/{academicPolicy}/reset-approval` → standalone test-policy approval reset.

These dedicated routes must be declared **before**:

`DELETE /admin/system-maintenance/test-data-cleanup/{type}/{id}`

because `academic-policies/{id}` also structurally matches the generic two-segment route. Losing the dedicated routes (for example by replacing `routes/web.php` with an older package) causes the Academic Policy Clean / Clean Chain action to hit the wrong route or return 404.

### Clean Chain behavior

If the selected Academic Policy belongs to an amendment/version chain, Clean Chain removes the complete test chain only when the dependency preview reports no downstream operational references. Approval request stages and requests are removed before policy configuration and version records.
