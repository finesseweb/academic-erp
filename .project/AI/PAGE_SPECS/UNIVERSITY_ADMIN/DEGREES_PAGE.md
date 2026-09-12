# Degrees

## Status
Implementation: IMPLEMENTED. Review: PENDING_REVIEW. Implemented 2026-08-21.

University-owned award master linked to an active Degree Level. Per-University code is unique; optional typical duration, ordered display, description and non-destructive lifecycle are supported. Permissions: `degree.view/create/update/disable`. Inertia CRUD uses the shared academic-master UI, inline validation, pending states, semantic messages, confirmation and four-theme tokens. Audit events: `DEGREE_CREATED`, `DEGREE_UPDATED`, `DEGREE_STATUS_CHANGED`.
