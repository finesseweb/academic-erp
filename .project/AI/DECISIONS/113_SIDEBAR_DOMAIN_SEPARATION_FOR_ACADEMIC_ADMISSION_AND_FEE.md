# ADR 113 — Sidebar Domain Separation for Academic, Admission and Fee

Date: 2026-09-05
Status: Accepted / Implemented

## Decision

The ERP sidebar must reflect business-domain boundaries instead of placing operational Admission and Fee modules inside Academic Setup.

### University / platform navigation
- `Academic Setup` contains academic master/setup concerns only: Academic Sessions, Degree Structure, Program Setup, Course Setup, Curriculum, Academic Policies and Academic Calendar.
- `Admission Setup` is a separate top-level tree. University Reservation / Quota Categories and Admission Form Setup live here.
- `Fee Management` is a separate top-level tree. University Fee Setup lives here.

### College navigation
- `College Academic Setup` contains Program Offerings, Intake / Seat Capacity, Batches, Sections and College Academic Calendar.
- `Admission Setup` is a separate top-level tree. It contains Reservation / Seat Distribution and the complete admission workflow/setup sequence: selection rules, admission cycles, form setup, applications/eligibility, scores, interviews, merit/roster, document verification, seat allocation and admission confirmation.
- `Fee Management` is a separate top-level tree containing `Fee Setup` and `Fee Demands`.

## Rules

1. This is a navigation/information-architecture change only. Existing routes, permissions, ownership rules and business workflows remain unchanged.
2. Permission filtering continues to determine whether a tree or child is visible.
3. Admission and Fee modules must not be re-nested under Academic Setup merely because they consume academic context.
4. Future Payment, Adjustment, Fee Clearance and related fee operations belong under the separate `Fee Management` domain tree.
5. Future enrollment/student lifecycle navigation is its own downstream domain and must not be placed back inside Admission Setup once enrollment begins.

## Reason

Academic Setup defines the academic structure. Admission consumes that structure to select and confirm candidates. Fee consumes both academic/admission context to create financial obligations. Keeping these domains as sibling trees makes the workflow clearer, prevents the Academic Setup menu from becoming an operational catch-all, and provides a stable place for upcoming Fee Demand, Payment and Clearance modules.
