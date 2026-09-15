# Reservation / Seat Distribution Page

## Purpose
Configure an optional Reservation Plan for one exact effective Intake admission seat bucket. The plan starts INACTIVE, may be edited while INACTIVE, and must be explicitly activated before downstream Selection Rules consume it.

## Hierarchy
Program Offering → Intake / Seat Capacity → optional Reservation / Seat Distribution → Merit / Roster / Selection Rules.

## Plan-level lifecycle and actions
- Every Reservation Plan card must always show a visible `ACTIVE` or `INACTIVE` status badge.
- An INACTIVE plan exposes plan-level `Edit` and `Activate` actions to authorized users.
- An ACTIVE plan exposes `Deactivate` to authorized users.
- The admission seat bucket identity is immutable after creation. Plan Edit may change plan metadata/notes only; quota rows are maintained through their own Add/Edit/Delete actions.
- Quota allocations may be changed only while the plan is INACTIVE.
- Activation validates the current Intake bucket, unchanged basis capacity, at least one quota allocation, and legal vertical/horizontal totals.
- Deactivation is blocked while an ACTIVE Selection Rule depends on the plan.

## Permission consistency
`college_reservation.update` controls editing the INACTIVE plan and its quota rows. Explicit lifecycle permissions `college_reservation.enable` and `college_reservation.disable` control activation/deactivation. Any College role already trusted with Reservation update during this milestone is synchronized with the lifecycle permissions so the UI cannot expose quota editing while hiding the plan lifecycle required to complete the workflow.

## UI consistency rule
Plan-level actions belong in the Reservation Plan card header and must not be confused with quota-row actions. Status must remain visible beside the plan title. Follow the shared responsive dialog/form rules in `UI_UX_GUIDELINES.md`.

## Lifecycle action visibility
- Every Reservation Plan card must show the current `ACTIVE` / `INACTIVE` status in the card header.
- Plan-level lifecycle controls belong in the same header action group as plan Edit; quota-row Edit/Delete actions remain separate.
- An `INACTIVE` plan shows `Edit` and `Activate`; an `ACTIVE` plan shows `Deactivate` (editing allocations is locked while active).
- A College role that can maintain (`college_reservation.update`) a Reservation Plan must not lose lifecycle controls because of an unsynchronised dedicated enable/disable permission. Explicit `college_reservation.enable` / `college_reservation.disable` permissions remain valid, with update permission acting as the management fallback.
- Activate/Deactivate must use a confirmation dialog and backend dependency validation; do not expose a status badge without an available lifecycle action to an authorised plan manager.


## Lifecycle confirmation control contract
- The Activate/Deactivate confirmation dialog must always render an explicit footer containing `Cancel` and the requested lifecycle action (`Activate Plan` / `Deactivate Plan`).
- When using Inertia `<Form>` render-prop state, all form controls and footer actions must be returned from the same render-prop child; do not mix static children with a render-function child because the action footer can disappear at runtime.
- Reservation lifecycle buttons use the same neutral `outline` action styling as `Edit` and other secondary management actions. Status meaning is conveyed by the `ACTIVE` / `INACTIVE` badge, not by making the lifecycle action a primary-colored button.
