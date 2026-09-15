# ADR 085 — Admission Merit / Roster Generation and Locking

Status: IMPLEMENTED — OWNER QA REQUIRED
Date: 2026-09-02

## Context
Score Capture / Normalization and Interview Scheduling / Evaluation now produce the normalized inputs required by the locked Admission Selection Rule. The next frozen Admission transaction step is deterministic Merit / Roster generation before any Reservation / Quota seat consumption.

## Decision
Merit / Roster Generation is implemented as a separate Admission processing stage.

A candidate is in the generation population only when the Application Choice is:
- linked to the exact Selection Rule version being generated;
- Application status `SUBMITTED`;
- Choice eligibility status `ELIGIBLE`.

Generation never resolves a newer/current Selection Rule. It consumes the exact `college_admission_selection_rule_id` already locked on the submitted Application Choice. A locked rule may now be `RETIRED`; that does not invalidate historical candidates who locked that version while it was operational.

## Readiness Gate
Every candidate in the locked-rule population must be resolved before final generation:
- no Score row => pending;
- `PENDING_INTERVIEW` or any non-final score status => pending;
- `NOT_QUALIFIED` => resolved but excluded from ranking;
- `QUALIFIED` with a stored final weighted score => rankable.

If any candidate is pending, final generation is blocked. The system must never silently generate a partial final roster.

## Ranking Contract
1. Rank only `QUALIFIED` candidates.
2. Primary order: stored `final_weighted_score`, descending.
3. Apply structured Selection Rule tie-breakers in ascending priority order.
4. Free-text tie-break policy notes are never parsed or executed.
5. Missing tie-break data sorts after available data; if a criterion remains equal/unavailable, continue to the next configured criterion.
6. If all configured criteria remain equal, apply a deterministic non-policy fallback: Application Submitted At ascending, then Application Number ascending, then Application Choice ID ascending.

Supported tie-break values:
- Qualifying Exam Score => stored normalized Merit score;
- Entrance Score => stored normalized Entrance score;
- Interview Score => stored normalized Interview score;
- Date of Birth => Application date of birth;
- Application Submitted At => stored submission timestamp;
- Relevant Subject Score => first resolve a matching Merit-source label and use its normalized snapshot; otherwise resolve an exact Admission Form field key/label and use its numeric submitted value. Unresolved values remain null and ranking continues deterministically.

## Persistence and Immutability
Final ranked rows are stored in `college_admission_merit_entries` with:
- College / Intake / seat-bucket identity;
- Application / Choice / Score / exact Selection Rule version references;
- generation batch UUID;
- final rank and final weighted score snapshot;
- per-candidate structured tie-break snapshot;
- generated timestamp and actor.

Only one Merit entry may exist per Application Choice and only one rank per Selection Rule version. Once entries exist, Score Capture and Interview Evaluation are downstream-locked through their existing dependency guards. A generated roster is immutable in normal operations.

## Reservation / Quota Boundary
ADR 085 does **not** perform Reservation / Quota allocation or seat consumption. Merit ranking is produced first. Reservation / Quota and Seat Allocation consume this deterministic roster in the next implementation stage.

## Permissions
- `college_admission_merit.view`
- `college_admission_merit.generate`

The generate permission is sensitive and College-delegable. SUPER_ADMIN and COLLEGE_ADMIN receive it by default through the permission migration.

## Audit
Final generation emits `COLLEGE_ADMISSION_MERIT_ROSTER_GENERATED` with the exact rule/version, batch, ranked count, excluded-not-qualified count and ranked choice IDs.

## Next Frozen Step
Merit / Roster QA → Seat Allocation / Consumption → Admission Confirmation → Student Lifecycle.
