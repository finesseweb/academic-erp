# ADR 051 — Admission Form Template Builder Runtime Restoration

Date: 2026-08-31
Status: Accepted

The pre-existing Admission Form Template Builder capabilities remain part of Stage 1 and must not be removed by Applicant Portal or academic-choice work.

University Admission Form Setup must load and expose:
- Steps
- Optional Panels / Sections
- Fields
- Field Options
- Generic answer-based Field Conditions and source fields
- Academic applicability scopes
- Current academic option lists
- Granular RBAC permissions for Template / Step / Panel / Field CRUD

The frontend must tolerate missing/null nested relation arrays during SSR by treating them as empty arrays instead of crashing.

College extensions continue to follow `Allow College Override` ownership rules; University-owned locked structure is not directly editable by College.
