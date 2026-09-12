# Admission Cycle Lifecycle + Selection Rule Test Cleanup Patch

Date: 2026-08-26

## Admission Cycle lifecycle UI

Admission Cycle is a lifecycle-managed operational record. When a user can update/manage the cycle, the card header must show the same Power icon lifecycle action used by Intake / Seat Capacity.

- INACTIVE -> Power icon opens Activate confirmation.
- ACTIVE -> Power icon opens Deactivate confirmation.
- ACTIVE -> Close action remains separate and explicit.
- CLOSED -> no lifecycle reopen action.
- The lifecycle control must not disappear merely because dedicated enable/disable permission flags are missing when the same role is already authorized to update/manage the cycle.
- Backend authorization must mirror the visible lifecycle-management rule.

## Test Data Cleanup

Merit / Roster / Selection Rules are first-class test-maintenance records and must appear as their own Test Data Cleanup entity group.

- Cleanup deletes structured tie-breaker children before deleting the Selection Rule.
- Cleanup is blocked when an Admission Application Choice or later Admission-stage record references the Selection Rule.
- Full Academic Reset continues to delete Application choices/applications first, then Selection Rule tie-breakers/rules, then Reservation/Intake/Offering parents.
