# Admission Form Setup & Internal Application Entry — Stage 1

Status: IMPLEMENTED_IN_PACKAGE / OWNER_QA_REQUIRED
Date: 2026-08-27

## Route
- Setup: `/college/{college}/admission-form-setup`
- Internal entry continues at `/college/{college}/admission-applications`

## Purpose
Provide a reusable, scoped form builder that generates extra application steps/fields while preserving the existing relational Admission foundation.

## Setup capabilities
- Create University- or College-owned templates (University action requires University permission).
- Assign a manager user.
- Choose governance: University Controlled, University Base + College Extension, or College Controlled.
- Choose applicability: REGULAR, DIRECT, BOTH.
- Add ordered steps.
- Add dynamic fields: TEXT, NUMBER, DATE, EMAIL, PHONE, TEXTAREA, SELECT, RADIO, CHECKBOX, MULTISELECT, FILE, IMAGE, YES_NO.
- Configure required/optional, placeholder, help text, options, file extension/max-size rules.
- Add scoped mappings at Degree Level, Degree, Program Template, Program Offering, Admission Cycle, plus College/University default.
- Activate template only after at least one active step/field exists.
- Configure scoped Application Fee rules including FREE/no-fee.

## Internal Application Entry behavior
- Admission Mode can be REGULAR or DIRECT.
- Core candidate/program/seat-bucket fields remain system-controlled.
- Resolved dynamic template is shown and its fields use ERP theme controls.
- Available seat bucket/category choices come from the active Program Offering/Intake/Reservation structure; custom form fields do not replace this linked choice.
- REGULAR requires an active Selection Rule for each chosen seat bucket.
- DIRECT can use a valid active Intake/seat bucket without a Selection Rule.
- Dynamic values and uploaded documents are stored against the existing application.
- The resolved template and Application Fee are snapshotted on the application.

## QA gate
Verify template creation, step/field rendering, mapping specificity, fee resolution, REGULAR submission, DIRECT submission, file validation, role/scope enforcement, and no regression in Score/Interview pages. After acceptance, resume Interview QA then Merit / Roster.

## Conditional fields and academic applicability — 2026-08-27

Field creation now includes two optional rule areas:

- **Answer-based condition** — select an existing non-file source field, operator and comparison value(s). Example: `Sports Quota? = YES` controls `Sports Certificate`.
- **Academic applicability** — University may target Degree Level, Degree, Program and Curriculum; College may additionally target its Program Offering and Admission Cycle.

UI behavior:
- Choice Options are shown only for configurable choice field types.
- File-size/extensions are shown only for File/Image fields.
- Required means required **when the field is visible/applicable**.
- Application Entry evaluates conditions live using ERP-themed controls.

Backend behavior:
- Scope is resolved from the selected Admission Cycle and its linked Offering/Program/Degree/Curriculum chain.
- Laravel repeats condition/applicability validation; client-side hiding is not authorization/validation.
- Hidden/no-longer-applicable values are removed on draft save.

## University override governance addendum
Admission Form Setup follows the Academic Calendar governance pattern.
- University template creation exposes `Allow College override` (default OFF).
- College can create an extension only from an ACTIVE University base with this flag ON.
- University templates are read-only on College setup screens.
- RBAC remains authoritative in addition to this governance gate.

## Curriculum selector rule
All Curriculum selectors used for Form Field academic applicability must show only the current approved ACTIVE Curriculum after amendment resolution. Superseded approved Curriculum versions remain historical and are not valid new selections.

### Conditional source field selection
- The builder may group answer-condition source fields by Step for readability.
- A University Base field may depend on any already-created non-file field in the same University template, regardless of Step order.
- A College Extension field may depend on any inherited University Base non-file field or any already-created non-file field in the College Extension, regardless of Step order.
- FILE and IMAGE fields cannot be condition sources.
- Edit Field must allow changing/removing the saved condition. Selecting `Always show` removes the condition.
- Existing-field dependency edits must reject self-reference and circular dependency chains.
- Academic applicability is resolved before answer conditions; a conditional child is not part of the effective runtime form when its source field is unavailable in that academic context.
- These rules supersede the earlier same/previous-Step restriction and align this page specification with ADR 032 and ADR 073.

## Current Academic Applicability Multi-Select Contract - 2026-09-12

This section supersedes the earlier single-value applicability wording.

- University Field create/edit uses searchable multi-selects for Degree Level, Degree, Program, and Curriculum.
- College Field create/edit additionally supports multiple Program Offerings and Admission Cycles.
- Values selected within one dimension use OR semantics; populated dimensions are combined with AND; an empty dimension means Any.
- Laravel validates University/College ownership and consistency across the selected hierarchy.
- Curriculum choices are limited to current approved ACTIVE versions. Program, Offering, Curriculum, and Cycle mismatches are rejected.
- The existing `college_admission_form_field_scopes` table stores the generated scope combinations; no parallel applicability table is used.
- A field may generate at most 500 applicability combinations.
- Runtime context is derived from Admission Cycle -> Program Offering -> Program Template -> Degree -> Degree Level plus the Offering's Curriculum.

Governing decision: `DECISIONS/166_ADMISSION_FORM_ACADEMIC_APPLICABILITY_AND_PROFILE_PREVIEW.md`.
