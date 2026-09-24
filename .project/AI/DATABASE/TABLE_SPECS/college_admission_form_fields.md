
## Student lifecycle mapping — ENR-0 / ADR 199 — 2026-09-16
- `student_data_policy` — `APPLICATION_ONLY|STUDENT_PROFILE`, default `APPLICATION_ONLY`.
- `student_profile_key` — nullable stable key used only when a dynamic field is intentionally promoted into Student Profile data.
- Dynamic fields never create dynamic columns on `students`.
- Existing hard-delete protection remains authoritative: a Draft/unused field may be deleted, but fields with application/condition/copy/comparison dependencies are protected. Historical used fields must be retained/retired rather than destructively removed.
