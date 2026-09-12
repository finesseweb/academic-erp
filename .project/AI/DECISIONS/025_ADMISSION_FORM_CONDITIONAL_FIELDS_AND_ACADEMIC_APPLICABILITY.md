# ADR 025 — Admission Form Conditional Fields & Academic Applicability

**Date:** 2026-08-27  
**Status:** ACCEPTED — Stage 1 prerequisite enhancement

## Decision

The Admission Form Builder supports two independent visibility layers without changing the protected core Admission relationships:

1. **Answer-based conditional display** — a dynamic field may depend on another dynamic field in the same resolved template inheritance chain.
2. **Academic applicability** — a dynamic field may be limited to Degree Level, Degree, Program Template, College Program Offering, Curriculum and/or Admission Cycle.

## Answer-based conditions

- Conditions store the source **field ID**, never the label/key alone.
- Supported operators: `EQUALS`, `NOT_EQUALS`, `IN`, `NOT_IN`, `CONTAINS`, `IS_EMPTY`, `IS_NOT_EMPTY`.
- File/Image fields cannot be condition sources.
- A target field marked `Required` is required **only while the field is visible**.
- Hidden conditional values are removed from active Application field-value storage on save/update.
- The UI performs live show/hide for user experience; Laravel repeats the same condition evaluation and remains authoritative.

Example: `sports_quota = YES` → show and require `sports_certificate`.

## Academic applicability

- Applicability is relational and references canonical academic IDs.
- Empty applicability means `All applications`.
- Within one applicability row, every populated scope must match the Application's resolved academic context.
- The resolved context is derived from the selected Admission Cycle → College Program Offering → Program Template → Degree → Degree Level and the Offering's locked Curriculum.
- Academic labels/names are never used as security/business keys.
- The server filters non-applicable fields before generating the Application form payload and validates applicability again during save.

Example: `graduation_marksheet` may apply to `Degree Level = PG`; `CAT scorecard` may apply to a particular Program; a regulatory declaration may apply to one Curriculum version.

## Governance

University base fields may define conditions/applicability and remain locked when inherited. College extension fields may depend on inherited University base fields and may additionally use College-specific Offering/Cycle scope where permitted by the existing RBAC model.

## Forward-link guarantee

These rules affect only dynamic Application data capture. Core fields — Admission Cycle, Candidate identity, Program Offering/seat bucket, Application Choice, Selection Rule lock, status and submission lock — remain relational and unchanged. After Stage 1 QA, the project resumes Interview QA and then Merit/Roster as documented.
