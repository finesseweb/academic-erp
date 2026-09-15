# ADR 192 — Semantic Action Icon Standard

Date: 2026-09-14
Status: ACCEPTED / IMPLEMENTED FOR CURRENT QA SURFACE

## Decision
All new ERP pages and materially modified user-facing workflows must use the project's single icon system (Lucide React) for recognizable actions. Icons are part of the interaction contract, not optional decoration.

## Rules
- Use stable semantic mappings for common actions such as Add, Edit, Delete, Save, Search, Filter, View/Expand, Refresh, Approve, Reject, Reverse, Refund, Cancel, Upload, Download and Export.
- Primary/important workflow actions should normally use icon + visible text.
- Financially sensitive actions such as Refund, Payment Reversal and Adjustment Reversal must use visible text plus a meaningful icon.
- Destructive actions must also use the project's destructive styling where applicable.
- Standard action icons should normally use `size-4` and align with the shared Button component.
- Icon-only controls are allowed only when the meaning is universally clear and must have tooltip/accessibility text.
- Do not mix icon families without a separately approved design decision.

## Current QA implementation
The Fee Adjustments / Reversal / Refund workflow now uses semantic icons for Search, Post Adjustment, receipt expansion/collapse, Refund, Adjustment Reversal and Payment Reversal. The application dialog provider also accepts an optional `confirmIcon`, allowing themed prompt/confirm actions such as reversal to retain the same semantic icon inside the modal.

## Scope note
This ADR is mandatory for future new/modified UI. It does not require a risky one-shot rewrite of every legacy page; legacy pages should be brought into compliance when they are materially touched or through an explicitly approved consistency pass.
