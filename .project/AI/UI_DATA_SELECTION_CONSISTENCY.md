# UI / Data Selection Consistency Contract

## Status
MANDATORY PROJECT-WIDE STANDARD

## Purpose
Prevent modules from independently inventing dropdown defaults, ordering, active/current filtering, dependency filtering, or historical-reference behavior.

This contract applies to every existing and future Laravel + React/Inertia page unless a PAGE_SPEC or approved ADR explicitly documents a different domain rule.

## 1. Current Academic Session Default
For every NEW session-dependent record:
- if the University has an Academic Session with `is_current = true` and `status = ACTIVE`, preselect it;
- this is a default only, not an automatic ownership/data migration;
- other sessions remain selectable only when the module's eligibility rule permits them;
- Edit/View must preserve the record's stored `academic_session_id`;
- changing the University's Current Academic Session must never rewrite existing records.

Examples:
- Add Curriculum -> Current ACTIVE Session default
- Add Academic Policy -> Current ACTIVE Session default
- Add Academic Calendar -> Current ACTIVE Session default
- Add College Program Offering -> Current ACTIVE Session default
- future Intake / Batch / Admission / Fee / Course Delivery / Attendance / Examination records -> apply the same rule when session-scoped.

## 2. Eligibility Before Convenience
Defaulting never bypasses domain eligibility.

A dropdown must first be filtered to records legally valid for that workflow, then apply default/preferred selection.

Examples:
- inactive/closed records must not appear where the workflow requires ACTIVE;
- College Program Offering Curriculum must be the derived Current `ACTIVE + APPROVED` version matching Program + Session;
- a scope-dependent list must match the selected parent/scope.

Backend validation remains authoritative even when the UI has already filtered the options.

## 3. Current / Historical Reference Rule
`Current` controls selection for NEW operational records only.

Existing records keep their stored foreign keys for audit/history. Never silently replace:
- Academic Session
- Curriculum version
- Policy version
- Program Template
- College
- any other governed historical reference

A future upgrade/migration workflow must be explicit, permission-controlled and audited.

## 4. Display Order Contract
When a master/configuration table has `display_order`:
- list/dropdown queries must order by `display_order` first;
- use a stable secondary sort (`name`, `sequence_no`, or `id` according to the domain);
- do not replace configured ordering with alphabetical or newest-first ordering without an approved PAGE_SPEC/ADR;
- new child records should default/suggest the next valid display order where the UI supports ordering;
- reordering must preserve uniqueness/sequence rules defined by the module;
- backend rules are authoritative for duplicate/invalid order values.

When no `display_order` exists, use the domain-defined stable ordering documented in the PAGE_SPEC.

## 5. Dependent Dropdown Contract
If B depends on A:
- changing A clears any now-invalid selected B value;
- B only shows values valid under the selected A;
- backend repeats the relationship validation;
- Edit mode may display the stored historical value even if it is no longer selectable for a new record, when history must be preserved.

Examples:
`Academic Session -> Program -> Current Curriculum`
`Degree Level -> Degree`
`Curriculum Term -> Slot -> Course Mapping`

## 6. Status Filtering Contract
Do not treat `ACTIVE`, `CURRENT`, `APPROVED`, and `PLANNED` as interchangeable.

Each selector must explicitly document:
- allowed lifecycle/status values;
- whether Current is required, preferred, or irrelevant;
- whether approved versions are required;
- whether historical inactive/previous records remain visible on View/Edit.

## 7. Create vs Edit
Create:
- apply safe project defaults;
- filter to currently eligible options.

Edit:
- load the stored identity/reference first;
- do not silently replace it with the newest/current/default value;
- only allow changing a reference if the module explicitly permits it and backend rules validate the change.

## 8. Frontend + Backend Pair Rule
Every important selection rule must exist at both layers where applicable:
- frontend = usable filtering/default/display;
- backend/service = authoritative ownership, status, relationship and lifecycle validation.

Frontend-only business rules are incomplete.

## 9. Future Implementation Checklist
Before implementing any new page, the developer/AI must check:
1. Is the page Session-dependent? If yes, Current ACTIVE default rule.
2. Does any selector have `display_order`? If yes, configured order first.
3. Does one selector depend on another? If yes, dependent filtering + reset.
4. Is there a current/versioned master? If yes, define Current vs historical use.
5. What statuses are eligible for new records?
6. What historical values must Edit/View preserve?
7. Is the same rule validated on the backend?
8. Is the rule documented in the PAGE_SPEC before QA complete?

## 10. Consistency Override
A module may intentionally differ only when:
- the domain requires it;
- the exception is written in its PAGE_SPEC or ADR;
- backend and frontend implement the same exception.

Silent one-off behavior is not allowed.


## Intake / Seat Capacity Application — 2026-08-25
- Current-session Program Offerings are preferred first for new Intake setup.
- Program Offering selector contains only ACTIVE College-owned eligible records.
- Discipline -> Specialization is a dependent selector.
- Program Template mappings define eligible Discipline/Specialization values.
- Allocation `display_order` is explicit and stable.
- Backend repeats all selector/status/capacity integrity rules.

## 11. Shared DatePicker Contract — 2026-09-01
- Every date-only input in existing and future ERP React/Inertia screens must use the shared `resources/js/components/ui/date-picker.tsx` `DatePicker` when that component satisfies the workflow.
- Native browser `input type="date"` must not be introduced inside Admission Form Setup, dynamic form-builder validation configuration, public Admission Application rendering, internal Admission Application rendering, Candidate/Student flows, or future modules without an explicitly documented exception.
- Dynamic `DATE` fields created through Admission Form Setup render with the same shared DatePicker in both public and internal application forms.
- Form-builder date configuration itself (for example Dynamic Date/Age Validation -> Custom Cutoff Date) must also use the same shared DatePicker so authoring and runtime date UX remain consistent.
- The shared component remains the single UI contract for calendar dialog, month/year navigation, display formatting, validation styling, and ISO `YYYY-MM-DD` submitted value.
- Domain-specific minimum/maximum dates and backend validation remain authoritative and may be passed into the shared component rather than replacing it with a page-local date control.
- If a future requirement cannot be supported by the shared DatePicker, extend the shared component first when the behavior is reusable; create a one-off date control only when a PAGE_SPEC/ADR explicitly approves the exception.
