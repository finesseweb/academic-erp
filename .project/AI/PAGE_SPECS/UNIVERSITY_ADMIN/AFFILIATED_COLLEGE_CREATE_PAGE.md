# Create Affiliated College Page

Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW


> Compatibility note: historical filename retained. The page creates an **Affiliated College under the University**.

## Identity
- Module: University / Affiliated College Management
- Route: `/super-admin/colleges/create`
- Permission: `college.create`
- Delivery phase: SA-05

## Purpose
Create a new College affiliated with the University.

## Initial Fields
- College name
- College code
- affiliation/type/status
- official email
- official phone
- address basics
- timezone
- Principal/College Admin reference when that workflow exists
- campus information if applicable
- optional branding/logo only when branding storage is implemented

## Initial Configuration Sections
Creation should establish only the required College identity. After creation, the College detail/configuration area can progressively configure:
- Administration
- Faculties / Schools
- Departments
- Programs
- Academic setup
- Faculty / Employees
- Students / Parents
- Fee Management
- Branding / Theme
- Access / Security

## Fee Management Position
College-level fee configuration and Installment Plans are part of the College domain, but should be implemented only when the Finance module is approved. University-fixed fee governance remains University-owned.

## API
Implemented as Inertia mutation: `POST /admin/colleges`.

## Audit
`COLLEGE_CREATED`

## References
- `../../DOMAIN/UNIVERSITY_HIERARCHY.md`
- `../../DOMAIN/FEE_GOVERNANCE_AND_INSTALLMENTS.md`

## Implemented Behavior
- Full-page responsive form for identity, affiliation/status, official contact, address, country, and timezone.
- Laravel Form Request validation, `college.create` enforcement, University ownership assigned server-side, transactional `COLLEGE_CREATED` audit, pending state, inline errors, and success toast.
- Principal assignment, campuses, branding, academic, access, and finance configuration remain deferred to their ordered milestones.

## Change History
- 2026-08-19: Implemented the minimum complete College identity creation workflow.
