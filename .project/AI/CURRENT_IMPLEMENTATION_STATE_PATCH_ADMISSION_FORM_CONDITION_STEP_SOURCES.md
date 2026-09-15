# Current Implementation State Patch — Admission Form Conditional Step Sources

- Stage 1 QA fix completed.
- Conditional source dropdown is grouped by form Step.
- University Base: same-step and earlier-step non-file fields are eligible.
- College Extension: inherited University Base fields plus same/earlier College-step non-file fields are eligible.
- Later-step sources are blocked in Laravel as well as the UI to prevent forward/circular dependencies.
- No database migration is required for this correction.
