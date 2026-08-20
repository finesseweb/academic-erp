# Academic Sessions Page

## Status
- Implementation: IMPLEMENTED
- Review: PENDING_REVIEW
- Implemented: 2026-08-21

## Contract
University-scoped list/create/update/status/current-session management. Codes are unique within the University; end date cannot precede start date; exactly zero or one session is current. Setting current activates the selected session and atomically clears the previous current flag. Closed/archived sessions cannot be selected as current from the UI.

## UX
Responsive token-based cards, empty state, shared modern DatePicker with direct month/year selection, inline errors, processing lock/spinner, lifecycle controls and current/status badges. Permissions: `academic_session.view/create/update/close/set_current`.
