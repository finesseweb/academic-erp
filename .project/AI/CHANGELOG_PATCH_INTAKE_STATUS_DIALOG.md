# Intake Lifecycle UI Aligned with Program Offering — 2026-08-25

- Intake now uses the same `StatusDialog` lifecycle pattern as College Program Offering.
- Power icon appears beside Edit when the effective enable/disable permission is available.
- Activation opens a confirmation dialog before PATCHing status.
- Deactivation uses the same confirmation lifecycle.
- In Discipline mode the Power icon is disabled until top-level Discipline capacity exactly equals Approved Capacity.
- Backend uses the existing College permission authorization path.
- No additional protected-role fallback is required because DB verification confirmed COLLEGE_ADMIN has correct enable/disable permissions at the correct College scope.
