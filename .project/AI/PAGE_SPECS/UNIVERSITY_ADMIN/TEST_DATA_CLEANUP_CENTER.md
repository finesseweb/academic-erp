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
