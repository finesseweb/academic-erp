# ADR 096 — Configurable Fee Category Master

## Status
ACCEPTED / IMPLEMENTED — 2026-09-04

## Context
Fee Foundation initially stored Fee Head category as a fixed string list such as ADMISSION, TUITION, EXAMINATION and OTHER. Owner discussion clarified that Fee Category is a reusable classification and should not be hard-coded or degree-specific.

## Decision
Fee Category becomes a configurable master above Fee Head.

Hierarchy:
`Fee Category -> Fee Head -> Fee Structure Item -> Fee Structure`

A Fee Category classifies a charge for grouping/reporting. A Fee Head represents the actual charge. Degree/Program-specific applicability and amount remain in Fee Structure, not Fee Category.

Examples:
- Category `Examination` -> Fee Heads `Regular Exam Fee`, `Backlog Exam Fee`, `Practical Exam Fee`.
- Category `Tuition` -> Fee Head `Tuition Fee`; BA/BSc/BTech amounts may differ in their Fee Structures without creating degree-specific categories.

## Ownership and inheritance
- University may maintain University Fee Categories.
- College Fee Management can use ACTIVE University Fee Categories as inherited read-only classifications.
- A College may add a College-specific Fee Category when the University master does not cover a legitimate local requirement.
- Category codes are unique across the University fee domain to prevent ambiguous inherited/local duplicates.

## Lifecycle
- Default common University categories are seeded ACTIVE to preserve the original ready-to-use foundation.
- Newly created custom categories start INACTIVE.
- An ACTIVE Fee Head requires an ACTIVE Fee Category.
- A Fee Category used by an ACTIVE Fee Head cannot be deactivated.
- An ACTIVE Fee Category used by an ACTIVE Fee Head cannot be materially edited until the dependent Fee Head is deactivated.

## Data correction
Legacy `fee_heads.category` string values are migrated to `fee_categories`, and `fee_heads.fee_category_id` becomes the authoritative relationship. Existing category data is preserved.

## Explicit non-rule
Fee Category is not tied directly to Degree, Program Template or Program Offering. Program-wise fee differences belong to Fee Structure scope and Fee Structure Item amount.


## UI scalability note (QA refinement)
Fee Management must not render Categories, Heads and Structures as one long vertical page. The shared University/College Fee Manager uses three in-page tabs (Categories, Heads, Structures), with record counts, search and compact pagination. Fee Structure items retain the existing expandable child pattern. This is a presentation/scalability rule only and does not change fee ownership or lifecycle rules.
