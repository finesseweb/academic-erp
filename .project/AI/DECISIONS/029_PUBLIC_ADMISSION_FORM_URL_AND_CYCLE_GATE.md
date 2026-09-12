# ADR 029 — Public Admission Form URL and Admission-Cycle Gate

## Status
Accepted for Stage 1 prerequisite QA.

## Decision
A College-owned Form Mapping (Program Offering + Admission Cycle) is the public application publication unit.

- University continues to own Base Form definition/governance.
- College continues to own operational mapping.
- A College user with `college_admission_form.map` may enable/disable the public link for that mapping.
- Enabling creates one stable opaque-ish slug; disabling does not destroy the slug/history.
- Public candidate URLs resolve the exact College + Program Offering + Admission Cycle mapping. A raw Form Template is never published directly.
- Public candidate submission is REGULAR admission only. DIRECT admission remains an authenticated College/internal entry path.
- The public page may render an Upcoming/Closed state, but submission is accepted only while the linked Admission Cycle is ACTIVE and today is within `application_start_date` through `application_end_date` inclusive.
- Mapping, Template, College and Program Offering must all remain ACTIVE. Backend rechecks every dependency at submission time.
- Public submission creates and immediately submits the existing `college_admission_applications` record with `entry_source=PUBLIC`; it does not create a parallel public-candidate transaction model.
- Exact Program Offering/Admission Cycle, Form snapshot, Fee snapshot, Intake/seat bucket and Selection Rule locking continue through the existing Admission Application service.

## Why
The mapping already represents the College's operational decision about which form applies to one Offering/Cycle. Publishing the mapping keeps public entry linked to the same downstream Eligibility → Score → Interview → Merit chain and prevents a public URL from floating outside the frozen Academic/Admission hierarchy.
