# ADR 108 — University Fee Structure College visibility matches exact Curriculum

## Status
Accepted — 2026-09-05

## Context
ADR 105 made recurring University Fee Structures bind to an exact current Curriculum so Fee billing periods use real Curriculum Terms instead of synthetic Program Template periods. College applicability matching was updated to compare the University Fee Structure `curriculum_id` with the College Program Offering Curriculum. However, the College applicability query selected only `academic_session_id` and `program_template_id`; `curriculum_id` was therefore absent on the loaded offering and every Curriculum-bound University structure failed the match, so the College UI showed no applicable University Fee Structures.

## Decision
When resolving University Fee Structures applicable to a College, the ERP must load ACTIVE College Program Offerings with at least `academic_session_id`, `program_template_id`, and `curriculum_id` and compare all configured scope dimensions.

A University Fee Structure is applicable to a College only when an ACTIVE College Program Offering matches:
- the same University,
- the same Academic Session,
- the selected Program Template when the University structure is program-specific,
- the exact configured Curriculum when the University structure is Curriculum-bound.

University structures remain University-owned. They are never copied into College-owned Fee Structures. OPTIONAL structures require an adoption record; MANDATORY structures are effective automatically.

## Consequences
- College Fee Structure count may remain zero while applicable University structures are displayed in their own section.
- Exact Curriculum identity is preserved from University policy through College applicability and later Fee Demand resolution.
- A College using a different Curriculum version will not accidentally inherit a University structure bound to another Curriculum version.
- Future Fee Demand must continue to snapshot the exact applicable University structure and actual Curriculum term/period rather than infer compatibility by label or Program Template duration.
