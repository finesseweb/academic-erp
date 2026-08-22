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
