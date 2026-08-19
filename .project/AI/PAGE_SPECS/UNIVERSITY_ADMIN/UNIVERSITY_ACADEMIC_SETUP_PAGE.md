# University Academic Setup Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Page Identity
- Page name: University Academic Setup
- Module: University Administration / Academic Governance
- Route: /admin/university/academic-setup
- Page type: configuration hub
- Delivery phase: University foundation

## Purpose
Provide University-level authorized users a single place to manage common academic structures and rules used across affiliated Colleges.

## Roles / Permissions
- Required page permission(s): university.academic_setup.view
- Action permissions: academic_session.*, degree_type.*, program_template.*, curriculum.*, examination_rule.*, grading_rule.*, academic_calendar.*
- Scope required: University

## Main Sections
- Academic Sessions
- Degree Types
- Program Templates
- Curriculum
- Examination Rules
- Grading Rules
- Academic Calendar

## Business Rule
University-wide definitions are authoritative unless a specific rule explicitly allows College-level configuration.

## Realtime Decision
REST only.

## Definition of Done
Responsive, theme-compatible, Laravel authorization enforced, audit sensitive changes, documentation synchronized.
