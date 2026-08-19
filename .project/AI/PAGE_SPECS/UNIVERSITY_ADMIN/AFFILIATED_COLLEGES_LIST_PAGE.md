# Affiliated Colleges List Page

Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW


> Compatibility note: this file keeps its historical filename, but the business concept is **Affiliated College Management under one University**, not unrelated multi-tenant affiliated Colleges.

## Identity
- Module: University / Affiliated College Management
- Route: `/super-admin/colleges` (preferred)
- Legacy route alias if already implemented: `/admin/university/colleges`
- Permission: `college.view`
- Delivery phase: SA-05

## Purpose
Allow University-level authorized users to view and manage Colleges affiliated with the University.

## UI
Show: College name, code, affiliation/status, Principal/College Admin summary, contact summary, optional campus count, active user count, created date and actions.

Filters: search, status, affiliation/type where required, server pagination.

## Actions
- View College
- Create College
- Edit College
- Activate / Deactivate according to permission
- Open College configuration

## College Hierarchy Preview
Each College is expected to own or operate, as applicable:
- College Administration
- Campus (optional)
- Faculties / Schools
- Departments
- Programs
- Batches
- Semesters / Terms
- Courses / Subjects
- Faculty / Employees
- Students
- Parents / Guardians
- College Fee Management

## College Fee Management
The College detail/configuration experience must provide entry points for:
- College Fee Structure
- Installment Plans
- Student Fee Assignment
- Demand / Invoice
- Collection / Receipt
- Dues / Late Fees
- Discounts / Waivers
- Refunds / Reversals
- College Finance Reports

University-fixed fee components remain governed at University level and must not become editable merely because the College can collect them.

## API
Implemented as Inertia web route: `GET /admin/colleges`.
Compatibility alias may be retained temporarily if existing implementation uses `/admin/university/colleges`.

## Implemented Behavior
- Server-side search, status/affiliation filters, sorting foundation, 15-row pagination, summary cards, responsive table overflow, first-use/filtered empty states, and permission-aware actions.
- Activate/deactivate is a confirmation workflow with pending state and preserves history.
- Principal/Admin displays the optional linked user; campus/user counts are intentionally deferred until those entities exist rather than fabricated.
- REST/Inertia only; no WebSocket behavior.

## Change History
- 2026-08-19: Implemented list, filters, pagination, empty states, permission-aware navigation/actions, and audited status lifecycle.

## References
- `../../DOMAIN/UNIVERSITY_HIERARCHY.md`
- `../../DOMAIN/FEE_GOVERNANCE_AND_INSTALLMENTS.md`
