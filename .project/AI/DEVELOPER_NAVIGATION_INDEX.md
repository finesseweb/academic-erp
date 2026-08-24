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
