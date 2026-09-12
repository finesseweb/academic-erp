# ADR 044 — Admission Form route reconciliation and MySQL-safe index names

## Decision
Admission Form Setup remains under the existing College RBAC route hierarchy. Applicant/public patches must never replace the full College Admission Form CRUD/publication route set with an older subset.

The College route contract includes template, step, panel, field, mapping, public-access, applicant-registration-settings and fee-rule actions. Public applicant routes remain outside the internal authenticated College route group.

All long-table migration constraints/indexes introduced for applicant academic preferences use explicit short names compatible with MySQL's 64-character identifier limit. This applies to UNIQUE indexes as well as foreign keys.

Application collection is independent of seat availability. Any legacy seat-selection flag defaults false and is not a prerequisite for accepting an application; seat allocation is downstream admission processing.
