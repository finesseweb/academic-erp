# DEVELOPER NAVIGATION INDEX

## If You Want To Know...
### What do I build next?
Read:
- `MASTER_DEVELOPMENT_HIERARCHY.md`
- `NEXT_WORKFLOW.md`
- `CURRENT_IMPLEMENTATION_STATE.md`

### What should a page do?
Read:
- relevant `PAGE_SPECS/...`

### Where should I create files?
Read:
- `PROJECT_FOLDER_STRUCTURE.md`
- `MODULE_NAMING_STANDARD.md`

### How should every page look/flow?
Read:
- `PAGE_FLOW_STANDARD.md`
- `REACT_FRONTEND_STANDARD.md`

### How should Laravel API/business logic be structured?
Read:
- `LARAVEL_BACKEND_STANDARD.md`

### How should database changes be made?
Read:
- `DATABASE_RULES.md`

### How do roles/permissions/scope work?
Read:
- `SECURITY/`

### What is the University/College hierarchy?
Read:
- `DOMAIN/UNIVERSITY_HIERARCHY.md`
- `MASTER_DEVELOPMENT_HIERARCHY.md`

## Rule
Never guess a folder, page flow or authorization pattern when the documentation already defines it.

### How should the ERP sidebar/navigation be structured?
Read:
- `MASTER_DEVELOPMENT_HIERARCHY.md` — Sidebar Navigation Presentation Rule
- `DECISIONS/006_HIERARCHICAL_SIDEBAR_NAVIGATION.md`
- `UI_UX_GUIDELINES.md` — Hierarchical Sidebar Tree Standard
- `THEMING/THEME_SYSTEM.md` — Sidebar Tree Token Rule

### Attendance eligibility / exception integration
- `DOMAIN/ATTENDANCE_ELIGIBILITY_AND_EXCEPTIONS.md` — permanent future contract connecting Academic Policy → Attendance shortage/condonation/special exemption → final Attendance Eligibility → Examination Eligibility.

### Academic Policy list navigation
- `DECISIONS/008_ACADEMIC_POLICY_CONFIGURE_MENU.md` — permanent UI rule: all Academic Policy rule sections belong under one scalable Configure menu.

### Academic Policy lifecycle
- `PAGE_SPECS/UNIVERSITY_ADMIN/ACADEMIC_POLICY_APPROVAL_LIFECYCLE.md` — validation → submission → approval → ACTIVE → amendment/versioning contract.

### Academic Policy cleanup implementation
- `PAGE_SPECS/UNIVERSITY_ADMIN/TEST_DATA_CLEANUP.md` — actual Policy reset/chain-clean dependency order and safeguards.

### Admission Form testing-only deactivation
- `DECISIONS/072_TEST_CLEANUP_ADMISSION_FORM_TEMPLATE_DEACTIVATION.md` — controlled Test Data Cleanup exception for ACTIVE -> DRAFT during development/QA.
- `PAGE_SPECS/UNIVERSITY_ADMIN/TEST_DATA_CLEANUP_CENTER.md` — UI/action contract, confirmation, public-mapping shutdown and audit behavior.

### Admission Form conditional rendering integrity
- `DECISIONS/032_ADMISSION_FORM_CONDITION_ANY_FIELD_PARENT.md` — governing any-existing-field parent rule.
- `DECISIONS/073_ADMISSION_FORM_CONDITIONAL_RENDERING_DEPENDENCY_INTEGRITY.md` — Edit condition, cycle safety, applicability-first runtime and dangling-dependency pruning.
- `PAGE_SPECS/COLLEGE_ADMISSION/ADMISSION_FORM_SETUP_AND_INTERNAL_ENTRY_PAGE.md` — builder/runtime behavior.

## Admission Form Runtime Validation (2026-09-01)
- Governing decision: `DECISIONS/074_ADMISSION_FORM_RUNTIME_VALIDATION_PARITY.md`
- Shared frontend validator: `resources/js/lib/admission-field-validation.ts`
- Public renderer: `resources/js/pages/public/admission-application.tsx`
- College internal renderer: `resources/js/pages/college-admission-applications/index.tsx`
- Authoritative backend validator: `app/Services/CollegeAdmissionDynamicFieldService.php`

### Admission Form builder delete integrity
- Decision: `DECISIONS/075_ADMISSION_FORM_BUILDER_DELETE_ACTION_INTEGRITY.md`
- University UI: `resources/js/pages/admin/admission-form-setup/index.tsx`
- College UI: `resources/js/pages/college-admission-form-setup/index.tsx`
- University backend: `app/Http/Controllers/UniversityAdmissionFormSetupController.php`
- College backend: `app/Http/Controllers/CollegeAdmissionFormSetupController.php`

- Admission Form runtime copy-rule parity: `DECISIONS/076_ADMISSION_FORM_COPY_RULE_RUNTIME_PAYLOAD_PARITY.md`
