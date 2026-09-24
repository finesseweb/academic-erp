# 2026-09-19 — ENR-6.4 Import Photo + Loading Scope Correction

- Fixed IMPORT Student Profile photo capability resolution for degree-scoped Admission Form mappings by resolving the authoritative Program Template + Degree context instead of trusting a partial eager-loaded relation.
- Preserved the single Student Profile photo lifecycle for ADMISSION and IMPORT students.
- Refined ADR198 shared loading: Inertia loading remains centralized, but its visual spinner/overlay is now scoped to authenticated application content instead of the full viewport/sidebar.
- Login/auth pages no longer render a page-loading overlay.
- No database migration or schema change.
