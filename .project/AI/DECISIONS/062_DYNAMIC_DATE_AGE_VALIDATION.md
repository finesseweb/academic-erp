# ADR 062 — Dynamic Date / Age Validation

Date/age rules belong to the existing field `validation_rules` JSON and are not hard-coded to DOB or any named field.

For any DATE field a DRAFT template author may configure:
- minimum age in completed years,
- maximum age in completed years,
- reference mode: current date or custom cutoff date,
- custom cutoff date when selected.

The backend dynamic-field validator is authoritative and calculates completed calendar years (not days/365), preventing birthday-boundary errors. The same rule is reusable for any DATE field where age-from-date semantics are appropriate. Existing DATE-to-DATE cross-field comparisons remain available independently.
