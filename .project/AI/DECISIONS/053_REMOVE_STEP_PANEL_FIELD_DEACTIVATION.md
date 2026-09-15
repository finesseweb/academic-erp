# ADR 053 — Remove Step / Panel / Field Deactivation

Date: 2026-08-31
Status: Accepted

Admission Form structural components do not expose Activate/Deactivate controls.

Reason:
- DRAFT templates are the editable workspace; unwanted Step/Panel/Field structure can be edited or deleted before activation.
- ACTIVE templates are live/frozen and must not be structurally changed.
- RETIRED/SUPERSEDED templates are historical/frozen.
- Changes to a live template should be made through a future Draft Revision/version workflow rather than mutating the live version.

Template lifecycle remains separate from component CRUD.
University College Override control remains unchanged.
