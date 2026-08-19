# REACT FRONTEND STANDARD — FROZEN

## Stack
- React 19
- TypeScript
- Inertia
- Vite
- Laravel 13 as the server application

## Page Location
Business pages live under:
`resources/js/pages/<module>/`

Reusable application components live under:
`resources/js/components/`

Reusable layouts live under:
`resources/js/layouts/`

Reusable hooks/utilities/types may live under:
`resources/js/hooks/`, `resources/js/lib/`, and `resources/js/types/` as appropriate.

Do not create a separate `frontend/` application unless a future architecture decision explicitly approves it.

## Routing
Laravel routes are authoritative for normal ERP pages.
Use Inertia navigation between Laravel-backed React pages.
Do not introduce React Router for the standard ERP navigation unless explicitly approved for a contained feature.

## Data and Mutations
Use Inertia page props and Inertia form/router patterns for normal internal ERP CRUD/workflows.
Do not create REST endpoints solely because the UI is React.
Create JSON/API endpoints only when justified by the feature.

## Authorization
Use permission-aware UI helpers for UX.
Laravel policies/gates/middleware remain authoritative.

## Forms
Use one consistent reusable form approach.
Map Laravel validation errors to fields.
Always implement pending, success, failure, dirty/unsaved and disabled behavior where relevant.

## State
Prefer local component state and server-owned Inertia state.
Do not introduce a global state library without a real cross-application need.

## Design System
Mandatory: read and follow `UI_UX_GUIDELINES.md` and `THEMING/THEME_SYSTEM.md` before UI implementation.
Use shared components, semantic tokens and a single icon system.
Professional loading, skeleton, toast/message, dialog, error and empty states are mandatory.

## Page Consistency
Follow `PAGE_FLOW_STANDARD.md` and the page's PAGE_SPEC.
