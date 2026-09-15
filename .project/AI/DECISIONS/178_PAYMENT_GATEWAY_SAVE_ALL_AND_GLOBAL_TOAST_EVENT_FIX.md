# ADR 178 — Payment Gateway Save All + Global Toast Event Reliability

Date: 2026-09-10
Status: IMPLEMENTED — OWNER QA REQUIRED

## Context
During ADR 177 QA, Fee Head → Gateway Product Code mapping persisted correctly, but two UX issues were identified:

1. Mapping rows required individual Save actions with no bulk Save All convenience.
2. Repeated server mutations that returned the same toast message could show a toast only once. A second successful action could therefore complete without visible feedback.

The toast issue is cross-cutting and must not be fixed only on the Payment Gateway page.

## Decision

### 1. Add Save All for Payment Gateway Fee Head mappings
The gateway mapping register now keeps row drafts client-side and provides a `Save All` action.

- Existing per-row Save remains available.
- Save All submits every row that currently has a non-blank Product Code.
- Blank, never-configured rows are ignored so institutions may map only applicable Fee Heads.
- Product Code uniqueness is still not imposed: multiple Fee Heads may intentionally share the same Product/Item Code and Settlement Code.
- The bulk endpoint applies the same College scope and permission checks as individual mapping save.

Route:
- `POST /college/{college}/payment-gateways/{gateway}/mappings/bulk`

Permission:
- `college_payment_gateway.manage`

### 2. Make toast feedback event-based globally
The shared Inertia middleware now attaches a unique feedback event identifier whenever a response contains a toast or validation-error feedback event.

The global `useFlashToast` hook observes this event identifier in addition to message/error props. Therefore two consecutive mutations returning the same exact message are treated as two distinct feedback events.

This is a shared ERP behavior, not Payment-Gateway-specific. Future pages using the standard server `toast` flash contract automatically receive the same repeated-toast reliability without page-level workarounds.

## Invariants
- Toast rendering remains centralized in the existing `AppLayout` / `useFlashToast` contract.
- No nested application layout is introduced.
- No schema migration is required.
- Existing individual mapping route remains supported.
- Existing Payment Collection / Allocation accounting behavior is unchanged.
- Gateway configuration remains foundation-only; provider checkout/webhook execution is not enabled by this ADR.

## QA Required
1. On Payment Gateway mapping, change two or more Fee Head rows and click Save All.
2. Refresh the page and verify every submitted mapping persists.
3. Click individual Save on one row; confirm success toast appears.
4. Click individual Save on another row that returns the same success message; confirm the success toast appears again.
5. Repeat any existing ERP action twice where the same server toast message is returned; confirm both actions show feedback.
6. Confirm single application header/sidebar remains unchanged.
