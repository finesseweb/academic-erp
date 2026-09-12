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

## Admission Seat Allocation cleanup — 2026-09-03
The cleanup center now lists `Seat Allocation / Consumption` test records. Cleaning one Seat Allocation first removes its Horizontal category child rows and then the allocation itself. Cleanup is blocked when future Admission Confirmation already references that allocation. Full Academic Reset removes Seat Allocation children/parents before Merit, Score and Application rows to respect RESTRICT foreign keys.

### Admission Document Verification cleanup — 2026-09-03
Test Data Cleanup exposes Document Verification records and their review-item counts. A verification cannot be individually cleaned while Seat Allocation references it. Full Academic Reset removes Seat Allocation first, then verification items/header, then Merit/Application children, preserving FK order.

## Applicant cleanup by Program Offering — 2026-09-03
The cleanup center exposes a dedicated `Applicants` section for applicant login/profile test identities.

Behavior:
- applicants are listed one account at a time;
- each row shows the Program Offering(s) derived from that applicant's linked Admission Application -> Admission Cycle -> Program Offering;
- the page provides a Program Offering filter, plus an `No Program Offering linked yet` option for registrations that have not produced a linked application;
- cleaning an applicant removes that applicant's test Admission processing graph child-first: Seat Allocation horizontal children -> Seat Allocation -> Document Verification items/header -> Merit entries -> Scores/Interviews -> Application dynamic/academic/course/program-choice children -> Applications -> Applicant Profile -> Applicant login User;
- the cleanup is blocked once the applicant is `STUDENT_ENABLED`, has `student_id`, or has downstream Admission/Student records;
- staff User/Role cleanup continues to preserve applicants; applicant deletion is available only from this explicit Applicants maintenance section;
- audit event: `TEST_APPLICANT_CLEANED`.

The Program Offering filter is a navigation/safety aid only. An applicant account is a single identity and may eventually own applications for multiple Program Offerings; therefore cleaning the applicant removes the whole test applicant identity and all of its cleanable applications, not only one offering-specific slice.

## Generated Merit / Roster cleanup — 2026-09-03
The cleanup center exposes a dedicated `Generated Merit / Roster` section separate from Selection Rule cleanup.

Behavior:
- one cleanup row represents the generated roster belonging to one locked Selection Rule version;
- the row shows the Selection Rule code/version, Program, bucket and generated ranked-entry count;
- cleanup deletes only `college_admission_merit_entries` for that Selection Rule, preserving the Selection Rule, Admission Applications/Choices and normalized Scores so QA can correct upstream test data and regenerate the roster;
- cleanup is blocked when any Seat Allocation references a Merit entry from that generated roster; dependent Seat Allocation test records must be cleaned first;
- exact record code confirmation and the existing `test_data_cleanup.manage` / environment guard remain mandatory;
- audit event: `TEST_COLLEGE_ADMISSION_MERIT_ROSTER_CLEANED`.

This action is intentionally roster-level rather than row-level because Merit generation is an immutable ranked snapshot and must be regenerated as one coherent list, not edited/deleted candidate-by-candidate.

## Admission Application direct cleanup rule (2026-09-03)
Admission Application cleanup is an aggregate cleanup action. Data owned by the application itself must not force the administrator to visit separate cleanup screens. Once independent downstream records have been cleared, cleaning one Admission Application removes its application-owned children in dependency order: Program Choices, Dynamic Field Values, Academic Preferences, Curriculum Course Choices, individual Interview/Evaluator records, normalized Scores, Document Verification items/header, private uploaded files, and finally the application row.

The application remains blocked only while independently processed downstream records still consume it: Merit/Roster entries, Seat Allocations, Admission Confirmation records, or Student references. These must be cleaned first. This distinction prevents orphan database rows/files while preserving safety around later lifecycle records.

## Admission Confirmation cleanup — 2026-09-04
The cleanup center now includes `Admission Confirmation / Approval` (`admissions`) as an Admission Processing target.

- Individual Admission cleanup is allowed only when no Student Enrollment/Student record consumes the Admission/Application.
- Seat Allocation remains blocked while an Admission record references it; clean Admission first.
- Full Academic Reset deletes Admission rows before Seat Allocation, Merit, Score, Verification and Application parents.
- Audit event: `TEST_ADMISSION_CONFIRMATION_CLEANED`.


## Batch Management cleanup — 2026-09-04
- `batches` is a dependency-aware cleanup target.
- Individual Batch cleanup is blocked by future `sections.batch_id` or `student_enrollments.batch_id` references.
- Full Academic Reset deletes Batches before Intake / Program Offering parents to preserve RESTRICT-FK ordering.
- Program Offering cleanup treats Batch rows as downstream blockers.

## ADR 190 Fee correction cleanup — 2026-09-12
Test Data Cleanup lists `fee_adjustments` and `fee_payment_refunds`. Cleaning a Refund restores its exact installment paid amounts and recalculates affected Demand balances. Cleaning an Adjustment recalculates its Demand. A Fee Payment with Refund children must not be deleted first; clean the Refund child before the Payment.
