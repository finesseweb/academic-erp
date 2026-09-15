# ADR 005 — University Is the Domain Root

## Status
Approved.

## Decision
The project is a University ERP. University is the top-level business/domain entity. A University may have multiple affiliated Colleges.

`SUPER_ADMIN` remains the highest University-level system role and operates the University Administration workspace. Super Admin is not a separate business/domain root.

All future data ownership, RBAC scope, themes, reporting, finance and module design must follow University -> Affiliated College -> narrower academic/resource scope.

Documentation is always read from `.project/AI/`.
