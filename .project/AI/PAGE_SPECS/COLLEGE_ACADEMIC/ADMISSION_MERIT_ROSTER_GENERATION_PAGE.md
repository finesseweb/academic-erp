# Admission Merit / Roster Generation Page

## Purpose
Execute deterministic Admission ranking after Score Capture and any required Interview Evaluation, using the exact Selection Rule version locked on each submitted Application Choice.

## Hierarchy
Program Offering → Intake / Seat Bucket → locked Selection Rule version → Candidate Eligibility → Score Capture → optional Interview → Merit / Roster Generation.

## Population
Group candidates by exact locked Selection Rule ID. Include only Application Choices whose Application is `SUBMITTED` and whose eligibility status is `ELIGIBLE`.

## Group Summary
Each rule/version group shows:
- Program and Session;
- effective seat-bucket label;
- rule code/version and lifecycle state;
- total eligible population;
- QUALIFIED count;
- NOT_QUALIFIED count;
- pending/incomplete count;
- generated/locked state and generation batch when applicable.

## Readiness
Final generation is disabled/blocked when:
- the rule is only `INACTIVE` draft;
- any candidate in the group has no completed Score result;
- any Interview-required candidate remains pending;
- no candidate is QUALIFIED;
- structured tie-breakers are absent;
- a final Merit / Roster for that exact rule version already exists.

A RETIRED rule remains valid for candidates that already locked that exact version.

## Ranking Preview
Before generation, display the deterministic ranking preview for QUALIFIED candidates using:
1. final weighted score descending;
2. ordered structured tie-breakers;
3. deterministic final fallback only when policy criteria remain tied.

The preview displays Final / Merit / Entrance / Interview values and the evaluated structured tie-break snapshot. UI preview may show the first 100 rows for responsiveness; final generation must process the complete candidate set.

## Final Generation
`Generate Final Roster` requires explicit confirmation. Generation is transactional and persists immutable rows in `college_admission_merit_entries` with a shared batch UUID.

After generation:
- ranked rows are read from persisted Merit entries, not recalculated for display;
- Score and Interview edits become downstream-blocked by existing guards;
- normal regeneration is prohibited;
- test-only cleanup may remove disposable test data through the existing Test Data Cleanup discipline.

## Reservation Boundary
Do not apply Reservation / Quota or consume seats on this page. That is the next Seat Allocation / Consumption stage.

## QA Gate
1. SUBMITTED + ELIGIBLE population only;
2. exact locked Selection Rule version used even if now RETIRED;
3. pending Score/Interview blocks final generation;
4. NOT_QUALIFIED candidates excluded without blocking generation;
5. final weighted score sorts descending;
6. every configured tie-break criterion/direction executes in priority order;
7. Relevant Subject Score resolves snapshot/form values safely and missing values do not create nondeterministic order;
8. deterministic fallback produces stable order for complete ties;
9. final generation writes every qualified candidate, not only the first 100 preview rows;
10. duplicate generation is blocked;
11. generated entries lock upstream Score and Interview edits;
12. College scope and permissions are enforced server-side;
13. no Reservation / Quota seat is consumed by this stage;
14. audit event records rule/version, batch and ranked candidate IDs.
