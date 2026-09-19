# ADR 198 — Shared Application Loading Infrastructure

Status: IMPLEMENTED — OWNER QA REQUIRED
Date: 2026-09-16

## Decision
Loading feedback is a shared application concern, alongside the existing global Toast and Theme-Native Application Dialog infrastructure.

`AppLoadingProvider` is mounted once around the Inertia application in `resources/js/app.tsx`. It listens to Inertia `start` and `finish` lifecycle events and exposes `useAppLoading()` so standard navigation/data-refresh requests have one authoritative loading state.

## Presentation contract
1. Every Inertia visit receives a small theme-native global loading indicator automatically.
2. Data-heavy pages may consume `useAppLoading()` to additionally block only the affected table/card/filter region while preserving useful existing data.
3. Button/form mutations use Inertia `processing` for their precise inline spinner, disabled state and pending label.
4. Non-Inertia async operations use shared Spinner/Skeleton/progress primitives with explicit local request state.
5. Duplicate page-local Inertia start/finish wiring is prohibited when the shared provider already represents that request.

## Why
Without a shared layer, newly implemented pages can accidentally omit loading feedback or implement inconsistent spinners. Centralizing the Inertia lifecycle makes the default safe and consistent while still allowing contextual loaders for tables, forms, uploads and long-running work.

## Fee Clearance migration
Fee Clearance now consumes `useAppLoading()` instead of maintaining its own `loading` state and `onStart`/`onFinish` callbacks. Its table overlay and disabled controls remain contextual, but the request state comes from shared infrastructure.

## Database impact
None.


## 2026-09-16 refinement — visible and contextual fetch feedback
- The provider owns a centered, theme-native blocking loading presentation for every Inertia visit; a corner-only indicator is insufficient as the canonical fetch state.
- The provider exposes `setNextLoadingLabel()` so pages may describe a known fetch while retaining the automatic `Loading data…` fallback for untouched/existing pages.
- Resource-specific pages should add contextual labels when materially modified; they must not create separate router lifecycle listeners.
- Student Fee Ledger is the first existing financial page migrated to contextual shared loading (`Loading fee ledger students…` / `Loading student fee ledger…`).

## 2026-09-19 refinement — content-scoped navigation spinner
- Global Inertia loading **state** remains shared so touched pages can keep contextual labels and button-level states.
- The visual overlay is no longer mounted at the application root. It is rendered inside `AppContent`, which keeps the sidebar usable/visible and limits the loading mask to the main content region.
- Auth/login layouts do not render `AppContent`; therefore login/auth pages have no page-loading overlay.
- Existing form processing spinners remain local to their buttons. This change does not replace upload/progress/skeleton patterns where those are more appropriate.
