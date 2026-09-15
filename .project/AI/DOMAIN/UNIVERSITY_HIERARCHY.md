# University ERP Organizational Hierarchy

## Purpose
This document defines the canonical business hierarchy of the ERP. The software represents one University at the top level and multiple affiliated Colleges under that University.

## Canonical Hierarchy

```text
UNIVERSITY
│
├── University Administration
│   ├── Super Admin
│   ├── Registrar
│   ├── Examination Controller
│   ├── Finance / Accounts
│   ├── University HR
│   └── University IT / System Admin
│
├── Affiliated Colleges
│   │
│   ├── College A
│   │   ├── College Administration
│   │   │   ├── Principal / College Admin
│   │   │   ├── Accounts
│   │   │   ├── Examination Cell
│   │   │   ├── Admission Cell
│   │   │   └── College HR
│   │   │
│   │   ├── Campus (optional)
│   │   ├── Faculties / Schools
│   │   ├── Departments
│   │   ├── Programs
│   │   ├── Batches
│   │   ├── Semesters / Terms
│   │   ├── Courses / Subjects
│   │   ├── Faculty / Employees
│   │   ├── Students
│   │   ├── Parents / Guardians
│   │   │
│   │   └── College Fee Management
│   │       ├── College Fee Structure
│   │       ├── Installment Plans
│   │       ├── Student Fee Assignment
│   │       ├── Demand / Invoice
│   │       ├── Collection / Receipt
│   │       ├── Dues / Late Fees
│   │       ├── Discounts / Waivers
│   │       ├── Refunds / Reversals
│   │       └── College Finance Reports
│   │
│   ├── College B
│   │   └── Same governed College structure
│   │
│   └── College C
│       └── Same governed College structure
│
├── University Academic Setup
│   ├── Academic Sessions
│   ├── Degree Types
│   ├── Program Templates
│   ├── Curriculum
│   ├── Examination Rules
│   ├── Grading Rules
│   └── Academic Calendar
│
├── University Finance Governance
│   ├── Fee Heads / Classifications
│   ├── Fee Policies
│   ├── University-Fixed Charges
│   ├── College-Configurable Rules
│   ├── Scholarship / Waiver Rules
│   ├── Late Fee / Penalty Rules
│   ├── Approval Rules
│   └── Consolidated University Finance Reports
│
├── Access Management
│   ├── Users
│   ├── Roles
│   ├── Permissions
│   └── Scope Assignments
│
├── Security
│   └── Audit Logs
│
└── System
    ├── Settings
    ├── Themes
    └── Notifications
```

## Authorization Scope Hierarchy
`University -> College -> Faculty/School -> Department -> Program -> Course/Class -> Own Record / Linked Child`

A permission alone never grants unrestricted data access. A valid authorization decision combines permission, active role assignment, scope, resource ownership/assignment, and business-state rules.

## Ownership Rule
Do not blindly place `college_id` on every table. Classify each domain as University-owned, College-owned, or shared/inherited.

## Explicit Finance Hierarchy

University Finance Governance
- Fee Heads
- Fee Policies
- University Fixed Charges
- Student Categories
- Admission / Fee Quotas
- Scholarship Schemes
- Discount / Concession Rules
- Waiver Rules
- Late Fee / Penalty Rules
- College Configurable Rules
- Finance Approval Rules
- Consolidated University Finance Reports

Affiliated College → College Fee Management
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
