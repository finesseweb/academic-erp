# ADR 017 — Student Admission Reservation Consumption Rules

Status: PRESERVED FOR FUTURE STUDENT LIFECYCLE IMPLEMENTATION
Date: 2026-08-25

## Hierarchy Link

University Reservation / Quota Category Master
→ University Reservation Policy / Rule
→ College Reservation / Seat Distribution Plan
→ Active Program Intake
→ Discipline
→ General / No Specialization OR Admission Specialization
→ Effective Admission Seat Bucket
→ Student Admission Processing / Candidate Scoring
→ Interview Scheduling / Evaluation when required by ACTIVE Selection Rule
→ Merit / Roster Generation
→ Vertical Seat Consumption
→ Horizontal Quota Fulfilment
→ Admission Confirmation
→ Student Lifecycle
→ Reservation / Intake Reports

## Core Rule

Vertical reservation and horizontal reservation must be tracked separately.

### Vertical
Vertical category answers:
"Which physical admission seat did the student consume?"

Examples:
- OPEN / UR
- OBC
- SC
- ST
- EWS

One admitted student consumes exactly one physical seat from the applicable
vertical/open seat bucket.

### Horizontal
Horizontal category answers:
"Which cross-cutting reservation requirement/benefit does this admitted student satisfy?"

Example:
- PwD

A horizontal quota does not create an additional physical seat and does not
reduce Open / Unreserved Remaining during seat-plan calculation.

## Example

Seat Bucket Capacity = 20

Vertical:
- OBC = 5
- SC = 3
- ST = 2
- EWS = 2
- Open / Unreserved Remaining = 8

Horizontal:
- PwD target = 1

Total physical capacity remains 20.

## Student Examples

### SC + PwD student
If an SC + PwD candidate is admitted:

- Physical seat consumed: SC
- Horizontal quota fulfilled: PwD
- Physical seats consumed: 1, not 2

If SC capacity was 3:
- SC consumed becomes 1
- SC remaining becomes 2

If PwD target was 1:
- PwD fulfilled becomes 1/1

### Another SC candidate after PwD fulfilment
A later SC candidate who is not PwD can consume another available SC seat.
PwD fulfilment remains 1/1.

### Another SC + PwD candidate after PwD target is already fulfilled
The candidate must not be rejected merely because the PwD horizontal target
has already been fulfilled.

If the candidate qualifies under the applicable admission/merit/roster rules
and an SC seat is available:
- consume one SC physical seat;
- preserve the student's PwD status;
- do not count a second required PwD fulfilment.

Reports must distinguish:
- PwD target required;
- PwD target fulfilled;
- actual number of admitted PwD students.

### Open / General + PwD candidate
An Open/General + PwD candidate can consume an Open seat when selected under
the applicable admission rules.

If the PwD target is still pending, that admission may fulfil it.
If the PwD target is already fulfilled, the candidate may still be admitted
through normal Open merit/eligibility; the admission must not create an extra
PwD seat.

## Priority Between Multiple PwD Candidates

Do NOT hard-code:
- SC + PwD always wins over Open + PwD; or
- Open + PwD always wins over SC + PwD.

Selection and adjustment must follow the applicable configurable
University/Government merit, roster, reservation and admission policy.

The ERP must keep these concerns separate:
1. candidate eligibility / merit / roster selection;
2. physical vertical/open seat consumption;
3. horizontal quota fulfilment.

## Student Admission Data Requirement

Future Student Admission implementation must be capable of recording at least:

- admission seat bucket;
- vertical admission category / physical seat category;
- zero or more applicable horizontal reservation categories;
- whether a horizontal target was fulfilled by this admission;
- exact active merit/roster/admission Selection Rule version used for selection;
- raw and normalized Merit/Entrance/Interview scores required by that rule;
- interview evaluation reference when Interview is a configured scoring component;
- admission status;
- audit trail.

Do not store only one generic `reservation_category_id`, because one student
can simultaneously be SC (vertical) and PwD (horizontal).

## Capacity Safety Rules

- Never add horizontal quota count to physical seat capacity.
- Never subtract horizontal quota from Open seats merely because it exists.
- Never consume two physical seats for one student because the student has
  both vertical and horizontal reservation attributes.
- Never double-count specialization child capacity against its parent
  Discipline capacity.
- Student admission must consume the exact effective admission seat bucket
  created from the active Intake structure.
- Admission must not exceed the physical capacity of that bucket.

## Policy Configuration Rule

Exact reservation percentages, roster adjustment, merit preference,
carry-forward, interchange/conversion, relaxation, and tie-breaking rules
must not be assumed globally or hard-coded.

They must be implemented later from the applicable University/Government
admission policy.

## Implementation Dependency

When Student Admission Processing is implemented, developers must
review this ADR together with:
- Intake / Seat Capacity rules;
- Reservation / Seat Distribution rules;
- active Academic Session;
- active Program Offering;
- active Curriculum/Academic Policy dependencies where applicable.

Reservation consumption must remain traceable back to the exact Intake and
Reservation Plan used at admission time.

## Interview / Selection Component Boundary
Interview is an Admission Processing concern, not a Student Lifecycle master. The ACTIVE Selection Rule decides whether Interview participates and at what weight/minimum. A later Interview Scheduling/Evaluation submodule records panel, schedule, evaluator scores and the normalized candidate Interview score. Merit/Roster generation consumes that normalized score together with other configured components. After seat allocation and admission confirmation, Student Lifecycle receives the resulting admission references; it does not recalculate or edit historical interview/selection results.
