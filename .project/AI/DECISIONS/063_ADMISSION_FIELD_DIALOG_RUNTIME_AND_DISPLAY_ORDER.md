# ADR 063 — Admission field dialog runtime safety and explicit display order

## Decision
Keep advanced validation/date-age rules in the existing Admission Form Builder, but make the lazy-mounted Add Field rules component defensive against incomplete/null relationship payloads. Add explicit `display_order` on Panel creation and Field creation as well as edit, at University and College scope.

## Behaviour
- Add Field must not crash when optional rule relationships or source arrays are absent.
- Panel and Field display order is configurable inside a Step; lower values render first.
- Existing automatic +10 ordering remains the backend fallback when order is omitted.
- No new parallel ordering system and no schema change.
