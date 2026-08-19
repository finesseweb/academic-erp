# Authorized Signatories Page

Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW

## 1. Page Identity
- Module: University Foundation
- Page: Authorized Signatories
- Laravel Routes: `/admin/university/signatories`
- Inertia Pages: `signatories/index`, `signatories/create`, `signatories/edit`
- Ownership: University
- Scope: Current University
- Page Type: List / Create / Edit / Lifecycle

## 2. Purpose
Maintain the people formally authorized to sign University records, the category of records they may sign, their appointment period, and active status.

## 3. Who Can Access
- View: `authorized_signatory.view`
- Create: `authorized_signatory.create`
- Update: `authorized_signatory.update`
- Activate/deactivate: `authorized_signatory.disable`
- All records are restricted to the current University.

## 4. User Flow
Authorized users open the searchable, filterable list; create or edit a signatory on a full page; and activate or deactivate an appointment through a confirmation dialog. Successful mutations return an ERP toast. Validation remains inline and entered values are preserved by Inertia.

## 5. List / Dashboard Content
- Columns: name/contact, designation, authority type, effective period, status, actions
- Filters: search, authority type, status
- Sorting: name, designation, effective-from, created date
- Pagination: server-side, 15 rows
- Empty and filtered-no-result states are distinct.
- No bulk actions in this milestone.

## 6. Create/Edit Form
- Presentation: full page
- Fields: full name, designation, authority type, email, phone, effective from, effective until, status, notes
- Authority types: General, Academic Records, Examination, Certificates, Finance
- Validation: identity/designation/type/start/status required; end date cannot precede start date; normalized email and bounded text lengths
- Shared ERP date picker is mandatory for both dates and must support direct month/year selection.

## 7. Business Rules
- A signatory is an appointment record and does not require a login user.
- Deactivation preserves the record and audit history; hard delete is not provided.
- Date range describes the formal appointment period. Status is an explicit operational control.
- Signature image/specimen upload is excluded until protected document storage, access, retention, and download policy are documented.

## 8. Backend Contract
- Controller: `AuthorizedSignatoryController`
- Requests: `StoreAuthorizedSignatoryRequest`, `UpdateAuthorizedSignatoryRequest`
- Service: `AuthorizedSignatoryService`
- Delivery: authenticated Inertia web routes; no separate REST API
- Props: paginated signatories, filters, summary, permission capabilities
- Audit: create, update, status change

## 9. Database
- Table/model: `authorized_signatories` / `AuthorizedSignatory`
- Relationship: University has many authorized signatories
- Indexes: university/status/effective date and university/authority type/status
- Historical safety: restrict University deletion and use lifecycle status instead of deletion.

## 10. React Files
- `resources/js/pages/signatories/index.tsx`
- `resources/js/pages/signatories/create.tsx`
- `resources/js/pages/signatories/edit.tsx`
- `resources/js/pages/signatories/signatory-form.tsx`

## 11. Premium UI / UX Behavior
Follow `UI_UX_GUIDELINES.md` and `PAGE_FLOW_STANDARD.md`: semantic theme tokens, icons, responsive cards/table, visible focus, hover and transition states, pending spinners with duplicate prevention, inline errors, shared toast messaging, confirmations, accessible labels, and four-theme contrast.

## 12. Responsive and Theme Behavior
Desktop uses summary/filter cards and a scroll-safe data table. Tablet/mobile stack controls and keep primary actions reachable. All colors use semantic tokens and must work in Premium Light, Premium Dark, Ocean Blue, and Emerald.

## 13. Realtime Decision
Standard Inertia requests are sufficient because this is low-frequency administrative master data.

## 14. Tests
Cover authentication, permissions, scope, filtering, validation, date-range validation, persistence, status lifecycle, audit events, Inertia props, TypeScript, lint, formatting, and production build.

## 15. Definition of Done
The documented workflow, business rules, RBAC, audit trail, database indexes, premium states, responsive behavior, themes, and automated checks are implemented without advancing to Users.
