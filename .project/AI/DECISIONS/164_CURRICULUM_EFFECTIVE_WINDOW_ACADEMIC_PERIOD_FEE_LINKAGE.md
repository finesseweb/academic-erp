# ADR 164 — Curriculum Effective Window → Academic Period → Fee Due Date linkage

Date: 2026-09-09
Status: Implemented; QA pending

## Decision
The Academic ERP must maintain one continuous authoritative validity chain for recurring/specific academic-period fees:

`Academic Session → current approved Curriculum → Curriculum Term → Academic Calendar Term Period → Fee Billing Period → Standard Due Date → Fee Demand Item snapshot → Installment Due Date (when used) → Late Fine`

No Semester/Year master is duplicated in Academic Calendar or Fee Setup. `curriculum_terms` remains the authority for Semester/Year identity.

## Curriculum Effective From/To is an outer validity boundary
When an Academic Calendar Term Period is assigned to a Curriculum Term, its Start/End dates must remain inside BOTH:

1. the owning Academic Session Start/End; and
2. the Curriculum `effective_from` / `effective_to`, when supplied.

The effective allowed window is therefore the intersection of the two. If a Curriculum boundary is blank, the Academic Session boundary is used for that side.

Example:
- Session: 01-Mar-2026 → 02-Mar-2030
- BA Curriculum Effective From: 01-Jul-2026
- BA Curriculum Effective To: 30-Jun-2029
- Semester 1 Academic Period: 01-Jul-2026 → 31-Dec-2026 = valid
- Semester 1 Start 01-Jun-2026 = blocked
- Any term ending after 30-Jun-2029 = blocked

Only ACTIVE Terms belonging to the current APPROVED Curriculum version for the same University + Academic Session may receive Academic Period dates.

## Academic Calendar UI
Academic Period selection remains hierarchical/searchable (ADR 163): Curriculum first, then the existing Curriculum Term.

The modal now exposes the effective allowed date window and constrains the project DatePicker to it. Backend validation remains authoritative and cannot be bypassed by direct requests.

## Fee Setup linkage
Fee Setup does not independently invent billing-period dates.

For `PER_TERM`, `SPECIFIC_TERM`, `PER_ACADEMIC_YEAR`, and `SPECIFIC_ACADEMIC_YEAR` charges:
- the Fee Structure resolves its Curriculum;
- the active University Academic Calendar for the same Academic Session is resolved;
- the exact Academic Calendar Term Period(s) for that Curriculum are resolved;
- Standard Due Date is allowed only inside that effective Academic Period boundary;
- Fee Setup defensively rejects an old/invalid Calendar Period that falls outside Curriculum Effective From/To.

The Fee UI receives the same effective period bounds used by backend validation and restricts the project DatePicker to those dates.

`ONE_TIME` remains a deliberate exception because Admission Initial / genuine one-time fees are not inherently a Semester/Year billing period. Their due-date source remains their own fee/admission policy; they must not be forced into a fake Curriculum Term.

## Snapshot and downstream rule
Once Fee Demand is generated, the Standard Due Date is snapshotted on the Fee Demand Item. Later configuration changes must not rewrite historical liability timing.

If installments are scheduled, each installment due date controls installment timing while the Fee Demand Item retains the original policy snapshot.

Late Fine (ADR 158) remains QA-deferred until updated to consume this finalized hierarchy:
- no installment: Fee Demand Item snapshotted due date;
- installment: installment due date.

## Integrity / no orphan-link rule
Recurring/specific-term Fee Due Dates must not be accepted without a resolvable active Academic Calendar Term Period for the same current approved Curriculum and Academic Session.

This preserves the project rule that financial configuration must not silently drift away from academic structure.

## Schema
No new migration in ADR 164. ADR 161 tables/columns remain authoritative.

## QA gate
Do not start Late Fine QA yet. First pass Academic Calendar + Fee Setup integration QA:
1. Curriculum Effective From/To narrower than Session.
2. Academic Period inside boundary saves.
3. Start before Curriculum Effective From blocks.
4. End after Curriculum Effective To blocks.
5. blank Effective From/To correctly falls back to Session boundary.
6. Fee Setup recurring period shows the same Academic Period bounds.
7. Fee Due Date inside period saves.
8. Fee Due Date outside period blocks in UI and backend.
9. Fee Setup blocks if applicable Academic Period is missing/invalid.
10. generated Fee Demand snapshots the Standard Due Date.

Only after PASS should ADR 158 Late Fine be revised/QA'd.
