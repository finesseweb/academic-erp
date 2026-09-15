# College Academic Setup Hierarchy

## Frozen Order
1. Program Offerings
2. Intake / Seat Capacity
   - Program approved capacity
   - Discipline capacity where applicable
   - optional child Specialization capacity under a Discipline
   - remaining General Discipline seats are derived automatically
3. Reservation / Quota
4. Batches
5. Sections
6. College Academic Calendar / allowed University overrides

## Rule
Later modules must consume the output of the preceding operational layer instead of bypassing it.

Examples:
- Intake references Program Offering.
- Reservation references the Intake seat bucket.
- Batch/Admission logic must use the configured offering/intake context rather than selecting arbitrary University masters.

This hierarchy supports Colleges with simple Program-level sanctioned intake and Colleges with Discipline/Specialization-level sanctioned intake without changing the underlying academic master architecture.


## Reservation / Quota Layer — 2026-08-25
Reservation / Seat Distribution follows Intake and consumes only effective admission seat buckets:
- PROGRAM
- DISCIPLINE_GENERAL
- SPECIALIZATION

Vertical quota partitions physical seats. Horizontal quota overlays physical seats.
Admission and Student Lifecycle must consume this layer rather than bypassing it.

## Merit / Roster / Selection Rules Layer — 2026-08-26
After the Reservation / Seat Distribution layer, implement a versioned Selection Rule against the exact effective Intake admission seat bucket. Reservation is optional per bucket; if configured, it must be ACTIVE before that bucket can proceed.

Reservation Plan
→ Merit / Roster / Selection Rule
→ Student Admission
→ Seat Consumption
→ Student Lifecycle

Do not let Admission select arbitrary Program/Discipline/Specialization masters. It must consume the active Selection Rule and its upstream Offering + Intake + Reservation context.


## Applications / Candidate Eligibility Layer — 2026-08-26
After an ACTIVE Merit / Roster / Selection Rule exists, Student Admission Processing begins with a Program-Offering-scoped Admission Cycle and candidate Applications. The Admission Cycle belongs to one exact ACTIVE College Program Offering; Academic Session/Program/Curriculum are inherited from it. Each Application therefore belongs to that same Program Offering and may contain ordered seat-bucket/specialization choices only within that offering. On submission, each choice locks the exact ACTIVE Selection Rule version and optional Reservation Plan context. Preliminary eligibility is stored per choice; score thresholds/ranking remain owned by later Score Capture / Merit processing.

Admission Cycle
→ Application
→ ordered Program Choice / effective Seat Bucket
→ locked Selection Rule version
→ Score Capture / Normalization (next)
→ Interview Scheduling / Evaluation when required
→ Merit / Roster Generation
→ Seat Allocation / Consumption
→ Admission Confirmation
→ Student Lifecycle

## Implementation checkpoint — 2026-09-03
Admission processing has now reached **Seat Allocation / Consumption** after Merit / Roster Generation. This does not mark the earlier College Academic Setup hierarchy complete.

Still mandatory and explicitly pending:
4. Batches
5. Sections
6. College Academic Calendar / allowed University overrides

The future Admission Confirmation / Student Enrollment integration must link confirmed students into the appropriate College academic structure when Batch/Section assignment becomes applicable. Do not bypass or forget these pending hierarchy layers.

### 2026-09-03 admission linkage checkpoint
Admission implementation has reached Document Verification + Seat Allocation, but this does not mark College Academic Setup complete. Batches, Sections and College Academic Calendar remain mandatory pending hierarchy items. Batch assignment must consume confirmed/enrolled students; Section assignment must sit under Batch; College Academic Calendar must be in place before timetable/attendance/teaching operations depend on dates.

## Implementation checkpoint — 2026-09-04
Admission processing has now reached **Admission Confirmation / Approval (implemented, Owner QA required)**.

The pending College Academic Setup order is now the immediate next implementation block:
**Batches -> Sections -> College Academic Calendar**.

Only after these layers are implemented and linked should Student Enrollment / Lifecycle assign the confirmed candidate into the operational College academic structure. Teaching-delivery modules must not bypass Batch/Section/Calendar.

## Implementation checkpoint — 2026-09-04 Batch Management
Admission Confirmation / Approval owner QA is accepted.

4. **Batches — IMPLEMENTED, OWNER QA ACCEPTED**
5. **Sections — IMPLEMENTED, OWNER QA ACCEPTED**
6. **College Academic Calendar / allowed University overrides — IMPLEMENTED, OWNER QA REQUIRED**

Section is implemented strictly under Batch and inherits the Batch's Program Offering, Curriculum, Academic Session and Intake context. It does not create or redefine seat capacity. Owner QA is accepted; College Academic Calendar is now the active milestone.


## Implementation checkpoint — 2026-09-04 College Academic Calendar
Batch and Section Owner QA are accepted. College Academic Calendar now adopts the University Academic Calendar per Academic Session and permits College changes only for University events explicitly marked `allow_college_override = true`. University-locked events remain read-only. After Calendar Owner QA, College Academic Setup is complete for the current pre-enrollment scope and Student Enrollment / Lifecycle is next.

## Fee dependency before Student Enrollment — 2026-09-04
The downstream implementation chain is now:
`Admission Confirmation -> Fee Foundation -> Admission Fee Demand -> Payment / Adjustment -> Fee Clearance -> Student Enrollment / Lifecycle`.

College Fee Structures consume an existing College Program Offering and never redefine Program, Curriculum, Academic Session, Intake, Reservation or seat capacity.
