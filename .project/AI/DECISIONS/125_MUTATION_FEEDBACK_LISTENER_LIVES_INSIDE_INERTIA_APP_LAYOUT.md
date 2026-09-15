# ADR 125 — Mutation feedback listener lives inside Inertia AppLayout

## Status
Accepted

## Context
Fee Demand mutation endpoints correctly returned session flash payloads such as `toast.type` and `toast.message`, but the UI did not reliably display them. The earlier global Sonner hook listened to `router.on('flash')`; in this project/runtime, Laravel session flash shared through `HandleInertiaRequests` is available in Inertia page props and is not reliably delivered through that router event.

Calling `usePage()` from the global `<Toaster />` is also invalid because the Toaster is mounted by `createInertiaApp.withApp()` outside the Inertia page context and caused a blank-page crash.

## Decision
1. The global Sonner `<Toaster />` is presentation-only and MUST NOT read Inertia page context.
2. `useFlashToast()` reads `page.props.flash.toast` and `page.props.errors` with `usePage()`.
3. `useFlashToast()` is mounted from `AppLayout`, which is inside the Inertia page context.
4. Server mutation outcomes use the existing shared `flash.toast` contract:
   - `success` for completed mutations,
   - `info` for protected/idempotent no-op outcomes,
   - `warning` for partial outcomes,
   - `error` for failures.
5. When a request returns validation errors without an explicit toast, the first validation error is surfaced as an error toast so a failed mutation is never silent.
6. Page-specific/internal CSS or a second notification system must not be introduced.

## Consequences
- Duplicate-protection messages are visible instead of silently skipping.
- Successful bulk/individual demand generation receives visible confirmation.
- Validation failures receive visible error feedback.
- The global Toaster remains safe from Inertia context crashes.
- Future ERP mutations must follow the same server-flash + AppLayout listener pattern.

## Supersedes / clarifies
This ADR refines ADR 124: the prohibition on `usePage()` inside the global Toaster remains valid, but the router `flash` event is not the canonical bridge for Laravel session-shared mutation feedback. The canonical bridge is now `AppLayout -> usePage() -> flash.toast/errors -> Sonner`.
