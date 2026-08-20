# College Access Audit

## Status
- Implementation: IMPLEMENTED
- Review: PENDING_REVIEW
- Implemented: 2026-08-21

## Contract
Read-only, paginated access-management history for one exact canonical `COLLEGE / college:{id}` scope. Access requires `college_audit.view` in that same College scope. Search, actor, event, resource, IP and date filters reuse the shared Audit Log presentation. No mutation route exists, unscoped and other-College events are excluded, and URL tampering is denied by the backend.

## UX
Uses shared theme tokens, responsive audit table/detail disclosure, filters, empty state, keyboard controls, validation, loading navigation, icons and four-theme contrast.
