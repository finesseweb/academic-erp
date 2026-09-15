# Changelog Patch — Admission Cycle Lifecycle + Selection Rule Test Cleanup

## 2026-08-26

- Admission Cycle lifecycle action now follows the existing Intake / Seat Capacity Power-icon pattern.
- A cycle manager who can update the cycle can see and use Activate/Deactivate lifecycle actions; dedicated lifecycle permissions remain compatible.
- Added confirmation dialogs for Admission Cycle Activate/Deactivate and kept Close as a separate final lifecycle action.
- Added `Merit / Roster / Selection Rules` as an individual Test Data Cleanup section.
- Selection Rule cleanup deletes structured tie-breakers first and refuses deletion when downstream Admission records reference the exact rule version.
- Full Academic Reset already contains Selection Rule/tie-breaker child-first cleanup and remains unchanged.
