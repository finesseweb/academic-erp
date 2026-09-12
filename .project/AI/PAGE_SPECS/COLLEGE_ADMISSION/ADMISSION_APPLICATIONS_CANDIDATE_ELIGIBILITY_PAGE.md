# Applications / Candidate Eligibility Page

## Route
`/college/{college}/admission-applications`

## Purpose
Capture College admission applications and ordered Program Choices after Merit / Roster / Selection Rules are configured. This milestone performs preliminary/basic candidate eligibility only; Merit, Entrance and Interview score thresholds are executed later by Score / Merit processing.

## Required Upstream Context
- College is ACTIVE.
- Admission Cycle is ACTIVE.
- Program Offering is ACTIVE and belongs to the Admission Cycle Academic Session.
- Intake / Seat Capacity is ACTIVE.
- Exact Admission Seat Bucket is still valid.
- If Reservation is configured for that bucket, its Reservation Plan is ACTIVE.
- An ACTIVE Merit / Roster / Selection Rule exists for that exact bucket.

## Search / Selection UX
Application creation uses the established scalable selector pattern:
1. searchable Admission Cycle;
2. searchable Program Offering filtered to the selected Cycle Academic Session;
3. searchable dependent Admission Seat Bucket filtered to the selected Program Offering.

A candidate may have up to 10 ordered Program Choices. Duplicate Intake + Bucket choices in one application are rejected.

## Candidate Fields
- Candidate Name
- Date of Birth
- Email (optional)
- Phone (optional)
- External / Portal Reference (optional)
- Remarks (optional)

Date of Birth is captured at Application stage because it may be required later by the structured Selection Rule tie-breaker.

## Application Lifecycle
- New application starts `DRAFT`.
- Only DRAFT applications are editable.
- `SUBMIT` revalidates every Program Choice against the current upstream chain and locks the exact ACTIVE Selection Rule version used by that choice.
- `submitted_at` is recorded and is the authoritative value for the `APPLICATION_SUBMITTED_AT` structured tie-breaker.
- `WITHDRAWN` is a terminal application-stage state in this milestone. Future downstream Score / Interview / Merit / Seat / Student references block withdrawal.

## Candidate Eligibility Boundary
Eligibility is stored per Program Choice, because one candidate may be eligible for one program and ineligible for another.

Statuses:
- `PENDING`
- `ELIGIBLE`
- `INELIGIBLE`

`INELIGIBLE` requires a reason.

This eligibility is preliminary/basic program eligibility. It MUST NOT duplicate or execute:
- Minimum Merit Score;
- Minimum Entrance Score;
- Minimum Interview Score;
- Minimum Final Weighted Score;
- Merit/Entrance/Interview weighting;
- structured tie-break ranking.

Those are owned by the ACTIVE Selection Rule and are consumed by later Score Capture / Normalization and Merit / Roster Generation.

## Historical Rule Lock
Draft choices may follow the currently active rule while being edited. On Application Submit the backend resolves the exact current ACTIVE Selection Rule again and persists its ID to the choice. Later Admission modules must consume that persisted rule version and must not silently relink a submitted application to a newer rule version.

## Permissions
- `college_admission_application.view`
- `college_admission_application.create`
- `college_admission_application.update`
- `college_admission_application.submit`
- `college_admission_application.eligibility`
- `college_admission_application.withdraw`

## QA Gate
Before Score Capture / Normalization:
1. create/edit DRAFT application;
2. use multiple ordered Program Choices;
3. verify Cycle -> Program Offering -> Seat Bucket filtering/search;
4. reject duplicate choices;
5. reject inactive/mismatched Offering, Intake, Reservation or Selection Rule context;
6. submit inside Application Cycle window and confirm `submitted_at` + exact Selection Rule ID are locked;
7. reject submission outside the Cycle application window;
8. confirm submitted application cannot be edited;
9. mark each choice ELIGIBLE / INELIGIBLE / reset PENDING;
10. require reason for INELIGIBLE;
11. verify cross-College IDs are rejected;
12. verify audit events;
13. verify Test Data Cleanup and Full Academic Reset dependency order;
14. verify responsive dialog/search selectors at desktop/tablet/mobile widths.

## Program Offering Anchor Correction — 2026-08-26
Admission Cycle now fixes exactly one Program Offering. The Application form must not ask the user to choose Program Offering again. After selecting an ACTIVE Admission Cycle, only searchable Admission Seat Buckets/specializations from that cycle's Program Offering are eligible. Multiple ordered choices remain allowed within that same offering. Cross-offering choices must be rejected server-side even when the offerings share the same Academic Session.

## 2026-09-01 Regular/Public application linkage correction

- `admission_mode` on the application is the candidate's actual route; template `BOTH` only means the template is reusable in both routes.
- A PUBLIC application is always created as `REGULAR`; the Candidate Eligibility page must show both the route (`REGULAR / SELECTION`) and source (`PUBLIC`).
- Applicant-facing application capture must not ask for or capacity-gate a seat bucket. Academic Discipline/Specialization/Course preferences remain the applicant input.
- On REGULAR submission, the backend derives exactly one downstream processing context from the stored academic preference + ACTIVE Intake + matching ACTIVE Selection Rule. This creates the `college_admission_application_choices` bridge used by Eligibility, Score, Interview and Merit.
- The automatic bridge does **not** test remaining seats and does not allocate a seat. Seat/reservation consumption remains downstream.
- If no valid context exists, or more than one context is ambiguous, REGULAR submission is blocked with a controlled setup message rather than creating an unlinked submitted candidate.
- DIRECT applications do not create this Selection Rule bridge and intentionally bypass Eligibility/Score/Interview/Merit rule-driven stages.
- Older QA submissions created before this correction can have zero choices. The UI must identify them clearly; test data should be cleaned/re-submitted rather than silently mutating historical SUBMITTED records on a GET request.

## 2026-09-01 — Internal Add Application capture parity

`Add Application` is an internal entry point into the same Admission Application foundation used by the public Applicant Portal. It must not expose Intake/Seat Bucket selection during application capture.

The internal draft captures:
- Admission Cycle and route (`REGULAR` or authorized `DIRECT`)
- core candidate identity/contact fields
- the resolved dynamic Admission Form Template
- the same curriculum-facing Discipline / effective Specialization / academic package or real course-choice inputs used by the public flow

For `REGULAR`, Submit resolves and locks the matching ACTIVE Intake + Selection Rule processing context from the saved academic preference, without asking the operator/applicant to choose a seat bucket. Eligibility then operates on that locked processing context.

For `DIRECT`, application capture remains on the same academic/form foundation, but Selection Rule / Eligibility / Score / Interview / Merit processing is bypassed; seat allocation remains downstream.

The internal UI must not reintroduce semester/term headings or applicant-facing seat-capacity/reservation bucket choice controls.


## Internal draft validation feedback (2026-09-01)
- Internal **Add Application / Edit Draft** must never fail silently.
- All server-side validation errors returned by the existing Inertia form contract are surfaced in a visible error summary inside the modal, in addition to field-level messages.
- This is especially important for cross-field/dynamic-form and curriculum-choice validation where the backend error key may not map to a currently visible leaf control.
- The existing backend remains authoritative; this change does not weaken or duplicate server-side validation.


## Internal form-template resolution consistency (2026-09-01)
- Internal Add Application must resolve the same ACTIVE Admission Form Mapping as the Applicant Portal for the selected Admission Cycle and Admission Mode.
- Resolver scope checks must use authoritative Program Template / Degree identifiers and must not depend on partially-selected eager-loaded relations used only for display.
- If the configured template contains a dynamic field that duplicates a core candidate field (for example another DOB), it remains a separate required dynamic answer; template authors should avoid such duplication unless a genuinely separate value is intended.
- A resolved template must be rendered before draft creation so backend required-field validation and visible inputs cannot diverge.

### Dynamic-field validation placement (2026-09-01)
- Server validation errors keyed as `custom_fields.{field_id}` must render only beneath the matching configured Admission Form field.
- A dynamic-field error must not be repeated at the end of every step/panel or duplicated in the general form-level error summary.
- Non-dynamic errors (cycle, candidate identity, academic preference, etc.) may continue to appear in the form-level summary and their own field location where applicable.
- Help text remains informational; validation feedback is visually distinct and immediately associated with the failing field.


## Admission Form mode enforcement (2026-09-01)
- The mapped ACTIVE Admission Form template is authoritative for which internal application routes are allowed.
- `REGULAR` template mode exposes only **Regular / Selection Based** in Add/Edit Application.
- `DIRECT` template mode exposes only **Direct Admission**.
- `BOTH` (`Regular + Direct`) exposes both routes.
- The frontend restriction is convenience only; `CollegeAdmissionApplicationService` re-checks the mapped template server-side on create/update and rejects a manipulated disallowed `admission_mode`.
- If no dynamic Admission Form is mapped for either route, the existing core-only application capture remains available for both modes for backward compatibility.
- Public applicant submissions remain Regular/Selection based; applicants do not choose Direct Admission.
