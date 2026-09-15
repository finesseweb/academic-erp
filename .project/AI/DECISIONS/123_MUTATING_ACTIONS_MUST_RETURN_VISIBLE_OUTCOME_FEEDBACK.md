# ADR 123 — Mutating actions must return visible outcome feedback

Date: 2026-09-07
Status: Accepted

## Decision
Every user-triggered mutation in the ERP (create, update, activate/deactivate, adopt/stop using, generate, cancel, delete, approve/revoke, cleanup, etc.) must produce a visible outcome message. A protected no-op is still an outcome and must never fail silently.

## Rules
- Successful mutation → visible success message.
- Integrity/duplicate protection that intentionally performs no mutation → visible informational message explaining what was protected/skipped.
- Partial/bulk result → visible message with generated/updated/skipped/error counts where applicable.
- Business warning → visible warning message.
- Failure → visible error/validation feedback; never rely on a silent redirect.
- Server remains authoritative for the outcome text. Frontend must not infer financial/business success from button clicks.
- Global Inertia flash `toast` is shared through `HandleInertiaRequests` and rendered by the existing project Sonner/Toaster hook so future modules can use the same project-wide feedback path.
- Do not introduce page-specific/internal CSS or a second notification system.

## Fee Demand application
- Re-running Individual demand when all applicable source fee items are already demanded returns an informational message and creates nothing.
- Re-running Bulk when all eligible candidates are already covered returns an informational summary, not a misleading success with zero generated records.
- Mixed Bulk runs report created and skipped counts while preserving idempotent duplicate protection.

## Rationale
Integrity protections are correct only when the operator can understand the result. Silent no-ops cause repeated clicks, uncertainty, and accidental support/admin work. This ADR makes explicit outcome feedback a project-wide implementation rule for future workflows.
