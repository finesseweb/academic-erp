# Test Data Cleanup Center

## Status
IMPLEMENTED_IN_REPLACEMENT_PACKAGE

## Navigation
System Maintenance
└── Test Data Cleanup

One sensitive permission:
`test_data_cleanup.manage`

## Menu-wise Sections
- Curriculum
- Courses
- Course Categories
- Course Types
- Program Templates
- Disciplines / Specializations
- Academic Sessions

## Curriculum Actions

### Reset Approval
Testing-only action:
APPROVED / ACTIVE / SUBMITTED / RETURNED / REJECTED
→ remove Curriculum approval request/stage test execution data
→ DRAFT
→ NOT_SUBMITTED
→ clear validation checkpoint
→ preserve Terms, Slots, Credits and Course Mappings

Blocked if downstream operational references exist.

Audit:
`TEST_CURRICULUM_APPROVAL_RESET`

### Clean
Removes the selected test Curriculum plus:
- approval execution request/stages
- course mappings
- slots
- terms
- Curriculum

Reusable master data is preserved.

## Course Cleanup
Course cleanup:
- removes Curriculum Course Mappings for that Course
- removes Course
- blocks when operational references such as Course Offering/Student Registration exist

This is intentionally more permissive for Curriculum Mappings because those are test structural dependencies.

## Category / Type / Program / Discipline / Session
These masters are cleaned only when dependent records are already zero.

Examples:
- Course Category with Courses => blocked
- Course Type with Courses => blocked
- Program Template with Curricula/Program Discipline mapping => blocked
- Discipline with Program Template/Curriculum Mapping => blocked
- Academic Session with Curricula => blocked

This forces dependency-aware cleanup order rather than hidden cascading destruction of large academic hierarchies.

## Confirmation
Every destructive/reset action requires exact record Code.

## Environment
Production execution remains disabled unless explicitly enabled with:
`TEST_DATA_CLEANUP_ENABLED=true`

Do not enable on live production data.

## Future Rule
As later ERP modules are implemented, their operational references must be added to cleanup guards before the module is allowed to be cleaned.

## Academic Policy lifecycle cleanup awareness

Maintenance/Cleanup must recognize `ACADEMIC_POLICY` approval requests and complete Academic Policy version chains. Cleanup remains test-data-only and must not bypass history/reference safeguards.

## Academic Policies tab — IMPLEMENTED

Maintenance UI now exposes `Academic Policies` alongside Curriculum and Academic Masters.

The tab supports:
- dependency preview;
- standalone test approval reset;
- complete test Policy version-chain cleanup;
- downstream-reference blocking;
- exact Policy Code confirmation;
- audit logging.

The implementation reuses the existing Test Data Cleanup permission/environment guard and does not introduce a separate maintenance design.

## Admission Form Template testing deactivation — 2026-09-01

The **Admission Form Templates** section exposes a maintenance-only **Deactivate for Testing** action for an `ACTIVE` template.

Flow:

`ACTIVE -> Deactivate for Testing -> DRAFT -> correct builder setup -> Activate normally`

Rules:
- action is visible only for ACTIVE Admission Form Templates;
- exact template Code confirmation is mandatory;
- `test_data_cleanup.manage` remains mandatory;
- Test Data Cleanup environment guard remains mandatory;
- the action is non-destructive: Template, Steps, Panels, Fields, configured rules, mappings and Applications are preserved;
- all enabled public applicant mappings belonging to the template are automatically switched off before the status changes to DRAFT;
- audit event: `TEST_ADMISSION_FORM_TEMPLATE_DEACTIVATED`;
- ordinary Admission Form Setup does not expose ACTIVE -> DRAFT.

This is a QA/development recovery provision only. Production-safe changes to a live template remain governed by the future Draft Revision/version workflow in ADR 053.
