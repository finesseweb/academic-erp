# ADR 191 — Theme-Native Application Dialog Standard

Status: IMPLEMENTED
Date: 2026-09-14

## Context
The ERP still contained several direct browser-native `window.prompt`, `window.confirm` and `window.alert` calls. These dialogs ignore the ERP theme, look inconsistent with the rest of the product, cannot use project-standard destructive styling, and create a dated UX for high-impact actions such as reversal, cancellation, deletion, rejection and refund-related workflows.

The project owner requires all user-facing prompt/confirmation/input dialogs to use the ERP's own themed UI, and requires this to remain the default rule for future development.

## Decision
Browser-native JavaScript dialogs are prohibited in ERP business UI.

All new and existing user-facing prompt/confirmation/alert needs must use the reusable application dialog layer:

- `resources/js/components/app-dialog-provider.tsx`
- `useAppDialog().prompt(...)`
- `useAppDialog().confirm(...)`
- `useAppDialog().alert(...)`

The provider is mounted once in `resources/js/app.tsx`, so business pages can invoke consistent dialogs without recreating modal infrastructure.

## UX rules
1. Dialogs consume semantic theme tokens and therefore work with Premium Light, Premium Dark, Ocean Blue, Emerald and validated custom themes.
2. Destructive actions must use destructive styling and explicit action labels such as `Reverse receipt`, `Delete rule`, `Cancel allocation`, etc.
3. Reason/input workflows should use the themed prompt dialog rather than browser `prompt()`.
4. Confirmation workflows should use the themed confirm dialog rather than browser `confirm()`.
5. Blocking informational messages should use the themed alert dialog or the established toast/flash system depending on the workflow.
6. Business validation remains server-side authoritative; the dialog layer is UX only.
7. Existing business behavior, routes, RBAC and accounting/domain rules must not change merely because a browser dialog is replaced.

## Existing replacements completed
The implementation sweep replaced browser-native dialogs in the currently detected React business pages, including:

- Fee Adjustments / Payment Reversal
- Program Intake seat allocation deletion
- University and College Admission Form Setup delete/error flows
- Curriculum delete / retire / restore
- Curriculum Term / Slot / Course Mapping deletion
- Admission Confirmation revocation
- Fee Benefit cancellation
- Online Fee Payment success alert
- Merit / Roster final generation confirmation
- Reservation / Quota allocation removal
- Admission Document Verification reject / waive / deficient reasons
- Seat Allocation cancellation reason
- Late Fine Rule deletion

## Future rule
No new `window.prompt`, `window.confirm`, `window.alert`, bare `prompt()`, `confirm()` or `alert()` may be introduced into ERP frontend business code. If a new dialog interaction is required, extend or reuse the application dialog layer instead.

## Consequences
- ERP dialogs remain visually consistent with the active theme.
- High-impact actions have clearer titles, descriptions and destructive affordances.
- Future UI behavior is centralized and reusable.
- Async handlers are required because themed dialogs resolve through Promises.
