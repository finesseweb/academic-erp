# ADR 105 — University Fee Academic Periods Use Exact Curriculum

## Status
Accepted — 2026-09-05

## Decision
University Fee Structures that use `PER_TERM`, `PER_ACADEMIC_YEAR`, `SPECIFIC_TERM`, or `SPECIFIC_ACADEMIC_YEAR` must select an exact ACTIVE + APPROVED Curriculum belonging to the selected University, Program Template, and Academic Session.

Program Template `term_structure` and `duration_terms` describe the planned academic shape only. They must not create parallel Fee-owned Semester/Trimester/Year academic identities.

The Fee UI derives recurring term labels and available term sequence positions from the selected Curriculum's ACTIVE `curriculum_terms`. Academic-year billing groups only complete sets of those real active terms. College Fee Structures continue to derive the same information from the exact Curriculum attached to the selected College Program Offering.

`fee_structures.curriculum_id` therefore records the University recurring structure's authoritative academic source. Fee period configuration may use term sequence as a billing-policy key, but it does not create a new academic-term ID. Future Fee Demand must resolve/store the actual `curriculum_term_id` from the applicable Curriculum.

University Fee Structure applicability to a College requires the College Program Offering to match Academic Session, Program Template, and, when configured, the exact Curriculum.

`ONE_TIME` structures do not require Curriculum because they do not address an academic term.

## Consequences
- University recurring Fee Structures can no longer show synthetic Semester 1..N solely from Program Template duration.
- Existing recurring University structures created before this ADR remain unbound after migration and must be edited to select the correct Curriculum before activation/use.
- No duplicate Semester/Year master is introduced in Fee Management.
