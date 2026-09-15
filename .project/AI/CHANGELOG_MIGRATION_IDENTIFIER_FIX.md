# Stage 1 Migration Identifier Fix — 2026-08-31

- Fixed MySQL 64-character identifier failure in `2026_08_31_140000_create_admission_application_academic_preferences.php`.
- Replaced Laravel auto-generated foreign-key names with explicit short identifiers.
- Added cleanup of partial tables left by a failed first execution so the same migration can be rerun safely during Stage 1 QA.
- No admission workflow or seat-allocation logic changed.
