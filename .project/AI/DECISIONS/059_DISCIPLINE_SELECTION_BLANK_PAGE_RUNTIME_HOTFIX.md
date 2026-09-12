# ADR 059 — Discipline Selection Blank Page Runtime Hotfix

Date: 2026-08-31
Status: Accepted

Selecting a Discipline activates the applicant academic-choice renderer. The no-semester package
renderer must be defensive against empty/null term, slot, mapping, and package arrays.

The academic-choice flow is aligned back to the previously proven pattern:
1. resolve applicable academic terms,
2. derive CHOICE-only terms,
3. build cross-term applicant-facing choice categories/packages,
4. keep Semester/Term and credit presentation hidden.

All array reads in this runtime path now use safe normalization before `.map`, `.filter`, `.flatMap`
or `.every`, preventing a malformed or partially populated curriculum response from crashing the
whole React page after Discipline selection.

No schema change.
