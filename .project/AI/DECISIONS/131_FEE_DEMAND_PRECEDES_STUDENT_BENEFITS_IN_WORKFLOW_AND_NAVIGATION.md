# ADR 131 — Fee Demand precedes Student Benefits in workflow and navigation

## Decision
Student Benefits is a demand-dependent operational workflow. A benefit cannot be assigned or financially posted until an applicable Fee Demand exists.

Canonical order:
`Fee Setup → Fee Demand → Student Benefits → Approval/Sanction → Demand Adjustment / Net Outstanding → Installment → Payment → Fee Clearance → Enrollment`

College Fee Management navigation must therefore display **Fee Demands before Student Benefits**. Scholarship / Benefits remains the policy/setup master and may appear before operational Fee Demands.

## Reason
Student Benefit assignment targets an existing Fee Demand and its fee-head items. Showing Student Benefits before Fee Demands suggested that benefit assignment could occur without a demand, which is not supported by the financial model.

## Scope
Navigation/workflow ordering only. No database, RBAC, calculation, sanction, or demand-adjustment behavior changes.
