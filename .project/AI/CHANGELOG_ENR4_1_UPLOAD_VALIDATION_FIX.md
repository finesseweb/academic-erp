# Changelog — ENR-4.1 CSV Upload Validation Compatibility Fix — 2026-09-17

- Owner QA: initial Student Import CSV upload failed with HTTP 500 at `Rule::extensions()`.
- Root cause: installed Illuminate validation API does not provide that static Rule helper.
- Replaced the incompatible helper with project-compatible file/size validation plus an explicit `csv` / `txt` filename-extension allow-list.
- Unsupported extensions now return a normal validation error instead of a server exception.
- No database/schema/RBAC change; no migration; no new domain table.
- ADR 204 remains OWNER QA IN PROGRESS pending upload, mapping, preview and transactional import verification.
