# Admission Cycle — Lifecycle UI Patch

## Rule

Admission Cycle is an operational lifecycle-controlled record and must expose its lifecycle action in the card header beside Edit, consistent with Intake / Seat Capacity, Reservation / Seat Distribution and other College Academic operational setup pages.

- `INACTIVE` cycle: show **Edit** and **Activate**.
- `ACTIVE` cycle: show **Deactivate** and **Close** (Edit remains available according to the existing active-cycle edit restrictions).
- `CLOSED` cycle: no reopen action.
- Lifecycle buttons use the project's neutral/outline secondary-action treatment; status itself communicates lifecycle state.
- A role allowed to update/manage an Admission Cycle must not receive a UI where Edit is visible but lifecycle controls silently disappear. Dedicated `enable` / `disable` permissions remain supported; update-authorized cycle managers are also lifecycle-authorized.
- Backend activation/deactivation validation remains authoritative. The UI must not bypass Selection Rule, submitted-application or closed-cycle guards.

## Shared UI consistency

Admission Cycle must continue to use the shared project `DatePicker` for all four date fields. Lifecycle patches must never regress the shared DatePicker implementation back to native `type=date` inputs.
