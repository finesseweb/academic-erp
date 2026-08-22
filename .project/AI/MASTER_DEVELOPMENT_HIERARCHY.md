# MASTER DEVELOPMENT HIERARCHY — FROZEN

## Status
FROZEN BASELINE

This file is the authoritative development sequence for the University ERP.
Do not silently reorder phases, skip prerequisites, or invent parallel modules.

Core security model:
`USER -> ROLE -> PERMISSION -> SCOPE`

## 01 — University Foundation
- University Profile
- Affiliated Colleges
- Authorized Signatories [planned]

## 02 — University Access & Security
- Users
- Roles
- Permissions
- Role Permission Matrix
- User Role Assignment
- Scope Assignment
- Audit Logs

## 03 — University Administration Roles
Handled through central User/Role/Permission/Scope:
- Super Admin
- Registrar
- Controller of Examinations
- University Finance
- University HR
- University IT / System Admin

## 04 — College Access & Role Management
- College Administrator Login / Assignment
- College Users
- College Roles
- College Role Permissions
- College User Role Assignment
- College Scope Assignment
- College Access Audit

Rules:
- University can create/assign College Admin.
- College Admin can create College staff users and College-scoped custom roles.
- College Admin may grant only delegated College permissions.
- College Admin cannot grant University-only permissions or cross-College access.

## 05 — College Administration Roles
Handled through College-scoped roles:
- Principal / College Admin
- Accounts Officer
- Admission Officer / Cell
- Examination Officer / Cell
- College HR
- Academic Coordinator
- Department Admin
- Custom College Roles

## 06 — University Academic Setup
1. Academic Sessions
2. Degree Levels
3. Degrees
4. Disciplines / Specializations
5. Program Templates
6. Course Categories
7. Course Types
8. Course / Paper Master
9. Curriculum
   - Curriculum Header
   - Manage Structure
     - Terms / Semesters
     - Curriculum Slots
       - Course Category
       - Slot Name
       - Display Order
       - Course Type
       - Mandatory / Choice
       - Minimum Selection
       - Maximum Selection
     - Course / Paper Mapping
     - Mapping Display Order
     - Semester Credit Totals
     - Curriculum Total Credits
     - Structure Validation
10. Academic Policies
    - Credit / Completion Policy [optional where required]
    - Attendance Rules
    - Examination Rules
      - Assessment components
      - Internal / External
      - Pass criteria
      - Backlog
      - Supplementary
      - Improvement
      - Maximum attempts
      - Attempt counting
      - Absent treatment
      - Grace
      - Revaluation
    - Grading Rules
    - Progression & Completion Rules
      - Semester / Year Promotion
      - Carry Forward / ATKT
      - Backlog Limits
      - Minimum Credits
      - Year Back
      - Detention
      - Maximum Attempts
      - Attempt Counting
      - Re-admission
      - Maximum Program Duration
      - Completion Eligibility
    - NEP / Flexible Academic Framework
      - Major / Minor
      - Multidisciplinary
      - Choice-based slots
      - Credit accumulation
      - Credit transfer / equivalence
      - Multiple Entry
      - Multiple Exit
      - Exit credit requirements
      - Re-entry
      - ABC / Credit Bank mapping
      - Exit awards
11. Academic Calendar
12. Academic Approval / Versioning

## 07 — College Academic Setup
- Program Offerings
- Curriculum Adoption
- Intake / Seat Capacity
- Reservation / Quota
- Batches
- Sections
- College Academic Calendar / allowed overrides

## 08 — Faculty / Employee Management
- Employee Master
- Faculty Master
- Department Assignment
- Designation
- Faculty Login
- Faculty Role Assignment
- Faculty Scope Assignment

## 09 — Student & Parent Access Management
- Student Master
- Student Login
- Student Role Assignment
- Parent / Guardian Master
- Parent Login
- Parent -> Linked Student(s)
- STUDENT -> OWN_RECORD scope
- PARENT -> LINKED_RECORD scope

## 10 — Student Academic Lifecycle
- Enrollment
- Program Assignment
- Curriculum Assignment
- Batch Assignment
- Section Assignment
- Course Registration
- Elective Selection
- Major / Minor Selection
- Credit Ledger
- Academic Status
- Year Back / Detention
- Re-admission
- Entry / Exit
- Program Completion

## 11 — Fee & Finance Setup
### University Finance Governance
- Fee Heads
- Fee Policies
- University Fixed Charges
- Student Categories
- Admission / Fee Quotas
- Scholarship Schemes
- Discount / Concession Rules
- Waiver Rules
- Late Fee / Penalty Rules
- Approval Rules
- University Finance Reports

### College Fee Management
- College Finance Dashboard
- College Fee Structure
- Installment Plans
- Student Fee Assignment
- Quota / Category Mapping
- Scholarship Assignment
- Discount / Concession Requests
- Waiver Requests
- Demand / Invoice
- Collection / Receipt
- Dues / Late Fees
- Refunds / Reversals
- Installment Rescheduling
- College Finance Reports

## 12 — Admission
- Admission Cycle
- Applications
- Program Choice
- Merit / Entrance
- Reservation / Quota
- Document Verification
- Seat Allocation
- Admission Approval
- Student Enrollment

## 13 — Course Delivery
- Course Offerings
- Faculty Allocation
- Timetable
- Rooms
- Class Scheduling

## 14 — Attendance Operations
- Attendance Entry
- Correction
- Approval
- Attendance Percentage
- Short Attendance
- Condonation
- Examination Eligibility

## 15 — Internal Assessment
- Assessment Setup
- Assignment
- Quiz
- Mid Semester
- Practical
- Marks Entry
- Marks Approval
- Internal Marks Finalization

## 16 — Examination Operations
- Examination Cycle
- Exam Form
- Eligibility
- Admit Card
- Examination Schedule
- Marks Entry
- Practical / Viva
- Backlog
- Supplementary
- Improvement
- Grace
- Revaluation

## 17 — Result & Progression
- Result Processing
- Pass / Fail
- Grade
- Credits Earned
- SGPA
- CGPA
- Backlog
- Promotion
- Year Back
- Maximum Attempt Evaluation
- Exit Eligibility
- Award Eligibility
- Result Approval
- Result Publication

## 18 — Certificates / Academic Records
- Marksheet
- Transcript
- Credit Statement
- Provisional Certificate
- Certificate / Diploma
- Degree
- NEP Exit Award
- Migration Certificate
- Authorized Signatures

## 19 — Reports & Analytics
- University Reports
- College Reports
- Academic Reports
- Student Reports
- Admission Reports
- Attendance Reports
- Examination Reports
- Result Reports
- Credit / NEP Reports
- Finance Reports

## 20 — System
- Settings
- Themes
- Notifications
- Audit
- Integrations
- WebSocket / Realtime only where justified

## Frozen Ordering Rule
Execute milestones in the order above unless a PAGE_SPEC explicitly defines a prerequisite that must come first or the owner explicitly approves a change.

## Important Curriculum Rules
- Category does not determine Credit.
- Same Category may have multiple Slots.
- Slot Name is curriculum-specific.
- Display Order is persistent.
- Course Master may provide defaults, but Curriculum Structure stores official curriculum-specific values.
- Curriculum versions preserve historical structures.

## Sidebar Navigation Presentation Rule
The frozen development hierarchy above also governs the authenticated ERP sidebar presentation.

Navigation must be grouped by business hierarchy instead of exposing every page as a top-level menu item.
The sidebar is a collapsible tree and must remain permission-aware.

Current grouping baseline:
- Dashboard
- Institution Setup
  - University Profile
  - Affiliated Colleges
  - Authorized Signatories
- User & Access Management
  - Users
  - Roles
  - Permissions
  - Audit Logs
- Academic Setup
  - Academic Sessions
  - Degree Structure
    - Degree Levels
    - Degrees
    - Disciplines
  - Program Setup
    - Program Templates
  - Course Setup
    - Course Categories
    - Course Types
    - Course / Subject Master
- College Management
  - College Users
  - College Roles
  - College Access Audit

Rules:
- Existing Laravel route URLs do not need to change merely to reflect this navigation tree.
- A parent node is visible only when at least one permitted child is visible.
- A child page remains protected by its existing backend permission; sidebar filtering is presentation only and never replaces backend authorization.
- College Management is shown only when the authenticated user has a College scope and at least one delegated College navigation permission.
- New modules must be added under the matching frozen hierarchy section instead of becoming unrelated top-level links.
- Use a third navigation level only when it improves clarity; avoid deep trees for simple modules.
- Render nested relationships with restrained tree connector lines using semantic theme borders.
- Accordion behavior is required: only one sibling branch at the same navigation depth remains open.
- The branch containing the current route automatically opens after navigation or refresh.
- Expand/collapse motion must be short, subtle, theme-safe and reduced-motion aware.
- Navigation presentation must not introduce modules that have not reached their approved implementation milestone.

### Curriculum Manage Structure Implementation Note — 2026-08-22
- Curriculum Header remains the entry point.
- Terms / Semesters are implemented contextually under a selected Curriculum Header.
- Curriculum structure may be mutated only while the Curriculum Header is `DRAFT`.
- `ACTIVE` and `RETIRED` versions are read-only so Curriculum versions preserve historical structures.
- Next frozen item is Curriculum Slots, beginning with Course Category, Slot Name and persistent Display Order.


## Curriculum Structure Update — 2026-08-22
- Curriculum Header: implemented.
- Terms / Semesters: implemented.
- Curriculum Slots Phase 1: implemented with Course Category, Slot Name and Display Order.
- Slot-level Credits are not introduced.
- Course / Paper Mapping remains a later milestone.
- Copy / Clone Structure is approved as the future reuse pattern when a new Curriculum/version needs an existing structure; source and target records remain independent.


### Curriculum Slots Phase 2 — 2026-08-22
- Course Type, Mandatory/Choice, Minimum Selection and Maximum Selection are implemented.
- Slot Credit is explicitly not part of this milestone.
- Mandatory Slots do not store Min/Max counts.
- Choice Slots require Min/Max counts and `Maximum >= Minimum`.
- Course Category and Course Type reuse existing University masters.
- Next frozen milestone: Course / Paper Mapping.


### Course / Paper Mapping — 2026-08-22
- Implemented after Curriculum Slots Phase 2.
- Reuses existing Course / Subject Master; no duplicate Course master.
- Context is Curriculum -> Term/Semester -> Slot.
- Same-University, ACTIVE, Course Category and Course Type compatibility is enforced.
- Mapping Display Order is deliberately deferred to the next milestone.
- Credit is not introduced in this mapping milestone.


### Mapping Display Order — 2026-08-22
- Implemented after Course / Paper Mapping.
- Order is scoped to a Curriculum Slot and controls mapped-course presentation.
- Reordering preserves a continuous Slot-local sequence.
- Credit remains outside this milestone.


### Course Mapping Academic Context — 2026-08-22
Program Template -> Discipline -> optional Specialization is reused in Curriculum Course Mapping. No duplicate Discipline/Specialization ownership is added to Course Master.


### Curriculum Validation Phase 1 — 2026-08-22
Non-credit structural validation is implemented after Course Mapping/Display Order. The source University ERP hierarchy still reserves Credit Summary before final full Curriculum Validation; therefore this is explicitly Phase 1 validation, not final credit validation.


### Credit / Clone Execution Order — 2026-08-22
The implementation order is corrected to avoid rebuilding Clone:
1. Curriculum Slot Credits
2. Credit Summary
3. Final Credit-aware Curriculum Validation
4. Copy / Clone Structure

Credits follow the original University ERP hierarchy under Curriculum Slots. Copy / Clone remains approved but is deferred until the complete credit-aware structure is ready.


### Credit Summary — 2026-08-22
Implemented after Curriculum Slot Credits and before final Curriculum Validation, matching the University ERP hierarchy. Clone remains after final validation.


### Final Credit-aware Validation — 2026-08-22
Completed after Credit Summary. Copy / Clone Structure is now the next implementation.


### Copy / Clone Structure — 2026-08-22
Implemented after Slot Credits, Credit Summary and final Credit-aware Curriculum Validation to avoid rework.
Supports complete Curriculum clone plus Semester and Slot convenience clones.


### Curriculum Assignment Safety Rule — 2026-08-22
Curriculum structural records may be hard-deleted only during DRAFT setup and before Student Group/Cohort assignment. Once operationally assigned, preserve history and use Curriculum versioning/cloning for structural change.


### Curriculum Reuse / Clone Rule — 2026-08-22
Full and granular cross-version reuse is supported. Partial clone target must remain within the same University and Program Template and must be DRAFT.


### Final Curriculum Delete Safety — 2026-08-22
Hard delete is a setup-correction feature only. Entire Curriculum, Term, Slot and Mapping deletion are allowed only before operational assignment and while the target Curriculum is DRAFT. ACTIVE/RETIRED or assigned academic history must never be hard-deleted.
