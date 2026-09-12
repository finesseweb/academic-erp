# ADR 121 — Fee Demand register groups by Admission/Application and stays collapsed

Date: 2026-09-05
Status: Accepted

## Context
A candidate/student can accumulate many fee demands over the lifecycle: Admission Initial, Semester/Trimester demands, Academic-Year demands, Examination demands and other controlled charges. Rendering each demand as a full top-level card makes the Fee Demand page grow vertically and repeats the same Admission/Application and candidate identity many times.

The main Fee Demand page also exposed Manual Recovery for legacy/missed admission-stage demand generation. Recovery is an exception/maintenance operation and was visually competing with the routine Bulk/Individual demand workflow.

## Decision
1. The normal College Fee Demand register is grouped by Admission/Application identity.
2. Each candidate/student appears once in the top-level register with aggregate Total, Mandatory, Enrollment Clearance and Outstanding amounts across that candidate's demands.
3. The candidate's demand collection is collapsed by default and is opened explicitly through `Demands (n)`.
4. Each individual demand inside the candidate group is also collapsed by default; Fee Items and cancellation controls appear only after `View Details`.
5. The register is searchable by Application Number, Admission Number, candidate/student name, Demand Number and billing-period/context text.
6. The visible register is paginated in compact pages so a large number of candidates does not create an endlessly long screen.
7. Manual Recovery is removed from the main Fee Demand screen. The backend recovery capability is retained as an exceptional maintenance mechanism and must not become the normal academic-period generation path.
8. Bulk Cohort and Individual generation remain the routine Fee Setup-driven actions and continue to share eligibility, duplicate-protection and snapshot rules.
9. No page-specific/internal CSS is introduced; the existing project Card/Button/Badge/Input and utility-class design language is reused.

## Consequences
- Multiple Semester/Year/Admission demands for one candidate no longer repeat the candidate as multiple top-level cards.
- Operators can find a candidate quickly and expand only the financial history they need.
- Demand financial history remains unchanged; this is a register/navigation presentation change, not a merge of financial records.
- Aggregates are display summaries only. Each child Fee Demand remains its own auditable immutable financial snapshot.
- Recovery remains possible for controlled maintenance but is not advertised as a routine workflow on the main Fee Demand page.
