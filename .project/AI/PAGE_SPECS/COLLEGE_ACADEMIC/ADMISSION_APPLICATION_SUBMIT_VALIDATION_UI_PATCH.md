# Admission Application Submit Validation UI Patch

- Admission Application submission remains constrained to the ACTIVE Admission Cycle's Application Start/End window.
- Backend validation is authoritative.
- Confirmation dialogs for Submit/Withdraw must render returned validation errors visibly inside the dialog.
- A failed lifecycle action must never appear to do nothing.
- Do not bypass application-window validation for convenience/testing; adjust the Admission Cycle dates when testing outside the configured window.
