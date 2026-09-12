# Current Implementation State Patch — Admission Form Cleanup + Public Link Open Mode

- Stage 1 template cleanup now explicitly removes field-condition rows before deleting fields/templates, fixing the `caffc_source_fk` 1451 failure.
- Full Academic Test Reset uses the same dependency-safe order.
- College public mappings now support `SAME_WINDOW` / `NEW_WINDOW`, defaulting to same window.
- Stage 1 remains in QA; frozen hierarchy resume point is unchanged.
