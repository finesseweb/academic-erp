# ADR 122 — Individual Fee Demand Student Selector Uses Server-Side Search

## Decision
The Individual Fee Demand workflow must not render the full eligible candidate/student population in a static HTML select. The operator searches by candidate/student name, Application No. or Admission No.; the server returns a bounded result set for the selected ACTIVE College Program Offering.

## Rules
- Search starts after at least 2 characters and is debounced in the UI.
- Search is scoped to the current College, selected ACTIVE Program Offering and CONFIRMED first-period cohort.
- Results are limited to 30 per query; the full cohort is not shipped in the Fee Demand page payload.
- Selecting an individual never bypasses the existing Fee Setup, period readiness, academic eligibility or duplicate-protection rules.
- Later-period eligibility remains controlled by authoritative Student Enrollment + Academic Progression when available.
- Existing project Input/select/Button components and theme are used; no internal/page-specific CSS is introduced.
