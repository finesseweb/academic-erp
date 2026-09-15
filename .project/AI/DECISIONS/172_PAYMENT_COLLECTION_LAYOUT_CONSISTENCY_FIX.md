# ADR 172 — Payment Collection Layout Consistency Fix

## Status
Accepted / Implemented

## Context
ADR 171 Payment Collection & Allocation initially wrapped its Inertia page with `AppLayout` even though the Academic ERP application shell is already provided by the parent layout/routing structure. This caused a nested application shell and duplicated header/sidebar chrome.

## Decision
- Payment Collection must follow the same page composition pattern as existing Fee Demand and other established ERP pages.
- The page MUST NOT render `AppLayout` locally when the parent application shell already owns it.
- The page renders only `Head` + module page content.
- No ad-hoc CSS or alternate shell is introduced.
- Existing ADR 171 business logic, RBAC, allocation, payment posting, receipt, cleanup, and routes remain unchanged.

## Design consistency contract
1. Exactly one application header/shell is visible.
2. Exactly one sidebar/application navigation shell is used.
3. Module content uses the existing project spacing, cards, buttons, inputs and table components.
4. Future finance pages must compare their layout wrapper against an existing canonical finance page before delivery.

## QA
- Open Payment Collection from the normal ERP navigation.
- Confirm only one application header is rendered.
- Confirm the page begins directly with `Payment Collection & Allocation` content inside the existing application shell.
- Confirm search, pagination and Collect Payment dialog still work.

## Migration
None.
