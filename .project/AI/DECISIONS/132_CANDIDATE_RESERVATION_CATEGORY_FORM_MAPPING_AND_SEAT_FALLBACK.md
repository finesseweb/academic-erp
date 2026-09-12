# ADR 132 — Candidate Reservation Category: Admission Form Mapping + Seat Allocation Fallback

## Decision
Candidate reservation identity and the reservation seat consumed are separate authoritative facts.

Flow:
Admission Form Template/Mapping → Candidate Category answer → Seat Allocation confirmation/snapshot → Admission → Scholarship eligibility.

- A College Admission Form mapping may optionally map one ACTIVE SELECT/RADIO field as the **Candidate Reservation Category Field**.
- At Seat Allocation the mapped answer is resolved against the University's ACTIVE Reservation Category by code/name. Common General/Open values resolve to `GENERAL`.
- If the mapping is absent or the submitted answer cannot be resolved, an authorized Seat Allocation user must manually confirm Candidate Reservation Category (including General / Unreserved).
- The candidate category is snapshotted on `college_admission_seat_allocations` with source `FORM_MAPPING` or `MANUAL`.
- Candidate category is distinct from `physical_reservation_category_id`. Example: Candidate Category = SC while Physical Seat Category = OPEN.
- Scholarship/Concession/Waiver category eligibility consumes only the snapshotted **Candidate Reservation Category**. It must not infer candidate identity from the physical seat bucket or horizontal target consumed.
- Existing reservation capacity, vertical/horizontal seat consumption, merit, roster and admission confirmation rules remain unchanged.

## Why
A reserved-category candidate can win an Open/General merit seat. Using the consumed seat category for financial-benefit eligibility would therefore produce incorrect results. Form mapping avoids duplicate entry while the controlled Seat Allocation fallback supports institutions/forms that do not collect the category.
