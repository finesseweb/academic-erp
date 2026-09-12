# ADR 071 — Admission Form Option Values Are Collision-Safe

## Status
Accepted — 2026-09-01

## Context
Admission Form option labels are administrator-defined and may contain punctuation or symbols. The previous implementation generated the stored option `value` with `Str::slug(label, '_')`. Different labels can collapse to the same slug; for example `A+` and `A-` both became `a`, which violated the field-local unique key on `college_admission_form_field_options` and surfaced a raw database 500.

## Decision
University and College Admission Form builders must use one shared option-value builder service.

- `label` remains the administrator-facing text.
- `value` is a deterministic machine identifier and must be unique within the field.
- Common symbolic meaning is preserved when practical (`+` => `plus`, trailing `-` => `minus`, etc.).
- If two different labels still normalize to the same value, a stable hash suffix is added rather than allowing a database constraint failure.
- Exact duplicate labels in one field are rejected with a controlled `options` validation error.
- `YES_NO` remains the canonical fixed pair `YES` / `NO`.
- The database unique constraint `(college_admission_form_field_id, value)` remains in place as the final integrity guard.

## Examples
- `A+` => `a_plus`
- `A-` => `a_minus`
- `AB+` => `ab_plus`
- `O-` => `o_minus`

This rule is generic; Blood Group is only one use case.
