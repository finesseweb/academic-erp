# ADR 069 — Admission Form Mode Is Authoritative for Application Route

Date: 2026-09-01

## Decision
The Admission Form Template `admission_mode` is the authority for which internal application route may use that mapped form.

- `REGULAR` => Regular / Selection Based only.
- `DIRECT` => Direct Admission only.
- `BOTH` => either route may be selected per internal candidate.
- Public Applicant Portal submissions remain Regular and never expose a Direct option.

The Add/Edit Application UI must filter/lock the route selector from the resolved mapped form. The backend must independently enforce the same rule so request manipulation cannot bypass governance.

If neither Regular nor Direct resolves a dynamic form mapping, core-only capture keeps both routes available for backward compatibility. Once a dynamic form is mapped, its mode is authoritative.

## Rationale
Template mode describes where the form is permitted to operate; application mode describes the route of one candidate. Linking them prevents a Regular-only form from being used to bypass selection via Direct Admission, and prevents Direct-only forms from entering merit/roster processing.
