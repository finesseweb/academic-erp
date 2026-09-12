# Changelog Patch — Admission Cycle Lifecycle Visibility

Date: 2026-08-26

- Fixed Admission Cycle cards where an `INACTIVE` cycle displayed Edit but no Activate action because lifecycle rendering depended only on dedicated `enable` / `disable` permission flags.
- Admission Cycle management now treats `update` authorization as sufficient operational lifecycle authority while preserving dedicated lifecycle permissions.
- Added permission synchronization for roles that already hold `college_admission_cycle.update`.
- Preserved the shared project DatePicker implementation in the Admission Cycle form.
- Documented the required card-level lifecycle pattern for future Admission modules.
