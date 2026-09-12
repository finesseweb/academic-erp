# Decision 010 — Academic Policy Scope Hierarchy

Date: 2026-08-25
Status: ACCEPTED FOR IMPLEMENTATION / QA PENDING

## Decision
Academic Policy scope is governed by the existing academic hierarchy:

`UNIVERSITY -> DEGREE_LEVEL -> PROGRAM_TEMPLATE -> CURRICULUM`

Degree Level is a first-class scope and references `degree_levels.id`. Undergraduate/Postgraduate/etc. must not be duplicated as free-text policy scope values.

## Storage contract
- UNIVERSITY: all specific scope foreign keys null.
- DEGREE_LEVEL: `degree_level_id` required; program/curriculum null.
- PROGRAM_TEMPLATE: `program_template_id` required; degree/curriculum null.
- CURRICULUM: `curriculum_id` required; degree null; optional program reference must match the curriculum when present.

## Implementation rule
Scope validation is enforced server-side. Frontend conditional fields are usability controls only and are never the sole enforcement layer. Clone and Amendment preserve the source scope references. Validation checkpoints include `degree_level_id` so changing scope invalidates the previous validation PASS.

## Future resolution rule
When a later runtime policy resolver is introduced, it must deliberately define how multiple applicable policy scopes are resolved. This change adds the scope hierarchy but does not silently introduce automatic inheritance/override behavior before that resolver is specified.
