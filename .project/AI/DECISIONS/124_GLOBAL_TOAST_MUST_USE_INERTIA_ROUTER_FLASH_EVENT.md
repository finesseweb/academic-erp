# ADR 124 — Global Toast Must Use Inertia Router Flash Event

## Status
Accepted

## Context
The ERP mounts the global Sonner `<Toaster />` from `createInertiaApp(...).withApp(...)`. That wrapper is outside the Inertia page component context. A flash-toast change replaced the existing router-event bridge with `usePage()`. Because `usePage()` requires an Inertia page context, the global toaster crashed the React tree and produced a blank page with:

`usePage must be used within the Inertia component`

## Decision
Global mutation feedback must continue to use the existing project-level Inertia router flash-event bridge:

`Laravel session toast -> HandleInertiaRequests shared flash -> Inertia flash event -> useFlashToast -> Sonner`

`useFlashToast()` MUST NOT call `usePage()` while it is consumed by the global Toaster mounted in `withApp`.

## Rules
1. Global Toaster stays in the project shell and uses the existing project theme.
2. Server mutations return `with('toast', { type, message })`.
3. The global toast hook subscribes to `router.on('flash', ...)`.
4. `usePage()` is permitted only inside components that are actually rendered under the Inertia page context.
5. A feedback enhancement must never be allowed to blank the application shell.
6. Protected no-op outcomes still require visible feedback per ADR 123.

## Consequences
- Duplicate-demand protection can report clear informational feedback without breaking the page.
- The pattern is reusable for create/update/delete/activate/adopt/generate/approve/cleanup actions.
- Future global UI infrastructure must respect provider/context boundaries.
