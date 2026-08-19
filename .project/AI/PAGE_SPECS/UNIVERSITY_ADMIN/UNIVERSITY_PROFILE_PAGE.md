# University Profile Page

Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW


## Identity
- Module: University Management
- Route: `/admin/university` (`/super-admin/university` compatibility redirect)
- Permissions: `university.view`; `university.update` for mutation

## Purpose
Represent the single University at the root of the ERP and provide the central entry point for University-wide configuration.

## Sections
- General University identity
- official contact/address
- branding/logo when implemented
- academic governance shortcuts
- finance governance shortcuts
- affiliated Colleges summary
- global/default theme and system settings where authorized

The implemented milestone provides identity/governance, official contact, and address/locale sections. Branding uploads and governance shortcut destinations remain deferred until their own milestones exist; no fake links or placeholder data are shown.

## Deferred Branding Contract

- University Profile is the authoritative upload/manage location for the University logo.
- Once the branding-storage milestone is approved and implemented, the resolved University logo/name must be supplied through the shared institution-branding context and component defined in `../../UI_UX_GUIDELINES.md`.
- The University identity is used for University-scoped users and for the central pre-authentication experience; a generic Laravel logo/name is never an application fallback.
- The same stored asset is reused on every approved University-branded surface rather than uploaded separately per page or report.
- This contract is planned only and does not change the current implemented scope of this page.

## Backend and Data

- Inertia controller: `UniversityController@show` and `UniversityController@update`.
- Validation: `UpdateUniversityRequest`; code is uppercase-safe and unique, website is HTTP(S), establishment date cannot be future, and timezone must be valid.
- University type is a controlled dropdown: Central, State, Private, Deemed-to-be, Open, Institute of National Importance, or Other. Selecting Other requires a specific classification.
- Establishment date uses the shared theme-aware calendar picker with direct month/year selection and previous/next navigation, rejects future dates, persists as a MySQL `DATE`, and is serialized to Inertia as `YYYY-MM-DD` to avoid browser/timezone display drift.
- Business service: `UniversityService`, with profile update and `UNIVERSITY_UPDATED` audit append in one transaction.
- Source of truth: the singleton `universities` row created by migration `2026_08_18_120000_create_university_profile_foundation`.
- Authorization resolves active role permissions at explicit `UNIVERSITY` / `university` scope. Frontend read-only state does not replace backend enforcement.
- Standard Inertia requests only; no REST-only duplicate endpoint and no WebSocket behavior.

## Premium UI / UX

- Shared authenticated shell, breadcrumbs, semantic cards, meaningful Lucide icons, inline validation, disabled/read-only state, pending submit spinner/copy, success toast/status, responsive one/two-column form, focus-visible controls, and sticky save action.
- Uses semantic tokens and supports Premium Light, Premium Dark, Ocean Blue, and Emerald.

## Hierarchy
University is parent of all Affiliated Colleges. See `../../DOMAIN/UNIVERSITY_HIERARCHY.md`.

## Change History

- 2026-08-18: Implemented the University Profile milestone with scoped RBAC, transactional audit logging, validation, four-theme UI, and feature tests.
- 2026-08-18: Replaced free-text University Type with a validated controlled dropdown and conditional Other classification.
- 2026-08-19: Fixed establishment-date round-trip serialization and replaced the native browser control with an accessible themed calendar picker.
- 2026-08-19: Added direct month/year selection to the shared picker and established it as the project-wide date-input standard.
- 2026-08-19: Documented the deferred University logo as the single reusable source for University-scoped application branding.
