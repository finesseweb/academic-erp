# College Administrator Login / Assignment
Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW

## Purpose
University administrators assign the protected `COLLEGE_ADMIN` role to a user at exactly one active College. The user authenticates through the existing central Fortify login; no duplicate login system exists.

## Rules
- Assignment requires `role.assign` and sensitive `college_admin.assign`.
- `COLLEGE_ADMIN` requires `COLLEGE` scope and a backend-resolved active College.
- Assignment converts the account to `COLLEGE_STAFF` and records its primary College.
- The role grants only College-management capabilities and never University-wide or cross-College access.
- Existing User Access UI, validation, pending states, audit and assignment protections are reused.

## Change History
- 2026-08-20: Implemented protected College Admin template and College-scoped assignment/login behavior.
