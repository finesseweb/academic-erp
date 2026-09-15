# Edit Affiliated College Page

Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW


> Compatibility note: historical filename retained. The page manages an **Affiliated College under the University**.

## Identity
- Module: University / Affiliated College Management
- Route: `/super-admin/colleges/:id/edit`
- Permissions: `college.view`, `college.update`
- Delivery phase: SA-05

## Purpose
Manage one affiliated College and its allowed University-governed configuration.

## Recommended Tabs / Sections
1. General
2. Administration
3. Campus
4. Faculties / Schools
5. Departments
6. Programs
7. Academic Configuration
8. Faculty / Employees
9. Students / Parents
10. Fee Management
11. Branding / Theme
12. Access / Security

## Fee Management Section
Provide navigation to:
- College Fee Structure
- Installment Plans
- Student Fee Assignment
- Demand / Invoice
- Collection / Receipt
- Dues / Late Fees
- Discounts / Waivers
- Refunds / Reversals
- College Finance Reports

The College can configure only fee areas allowed by University policy. `UNIVERSITY_FIXED` components are read-only at College level except for permitted operational actions such as application/collection.

## API
Implemented:
- `GET /admin/colleges/:college/edit`
- `PATCH /admin/colleges/:college`

## Audit
- `COLLEGE_UPDATED`
- status changes separately where appropriate

## References
- `../../DOMAIN/UNIVERSITY_HIERARCHY.md`
- `../../DOMAIN/FEE_GOVERNANCE_AND_INSTALLMENTS.md`

## Implemented Behavior
- Reuses the responsive College form for safe identity/contact/address updates.
- University ownership is verified server-side; `college.view` + `college.update` are required.
- Update and safe before/after audit data commit transactionally as `COLLEGE_UPDATED`.
- Future configuration tabs and finance links are not rendered until their corresponding milestones exist.

## Deferred Branding Contract

- The Branding / Theme section is the authoritative upload/manage location for this affiliated College's logo.
- Once the branding-storage milestone is approved and implemented, College-scoped users must receive the effective College logo/name through the shared server-resolved institution-branding context.
- The shared shell displays the College name, or its code in constrained space with the full name available accessibly. Missing logos use the shared College building icon and never Laravel branding.
- The same stored College logo is reused across every approved College-branded screen, report, receipt and document; individual pages must not maintain duplicate logo uploads.
- College branding cannot change or expand authorization scope, and Laravel must verify College ownership and upload permission.
- This contract is planned only and does not mark the Branding / Theme section implemented.

## Change History
- 2026-08-19: Implemented the College identity edit workflow; deeper configuration remains ordered and deferred.
- 2026-08-19: Documented consistent deferred College logo upload, resolution, fallback and reuse behavior.
