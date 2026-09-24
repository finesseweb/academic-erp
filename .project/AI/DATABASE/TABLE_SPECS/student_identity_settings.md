# student_identity_settings
ENR-3 supporting configuration table (ADR 203), one row per College. Stores future-assignment formats for Student UID, University Roll and Class Roll plus `updated_by`. Changing formats never rewrites assigned identities.

## ENR-3.3 addition
- `class_roll_scope` varchar(32), default `PROGRAMME_OFFERING`; allowed application values: `PROGRAMME_OFFERING`, `DISCIPLINE`.
- This setting controls future Class Roll sequence scope only; existing assignments are not renumbered.
