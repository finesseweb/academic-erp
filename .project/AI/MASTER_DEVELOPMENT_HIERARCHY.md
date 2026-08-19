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
       - Credit
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
