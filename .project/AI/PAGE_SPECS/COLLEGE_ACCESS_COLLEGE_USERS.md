# College Users
Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW

## Identity
- Routes: `/college/:college/users`, `/college/:college/users/create`
- Permissions: `college_user.view/create/update/enable/disable/reset_password`, always at the requested College scope

## Rules
- Laravel constrains lists and mutations by `users.primary_college_id`; URL tampering returns 403/404.
- Created accounts are always `COLLEGE_STAFF`, active, and linked to the authorized College; the browser cannot override ownership.
- Supports scoped list/search, create, edit, enable/disable and password-reset initiation with transactional audit through shared services.
- Uses the shared authenticated shell, validation, loading states, confirmations, semantic tokens, responsive table/form and empty state.

## Change History
- 2026-08-20: Implemented College-scoped staff list/create and lifecycle foundation.
