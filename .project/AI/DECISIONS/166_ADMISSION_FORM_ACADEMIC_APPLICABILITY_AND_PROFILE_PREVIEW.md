# ADR 166 — Admission Form Academic Applicability + Candidate Profile Preview

## Context
Admission Form Builder already supported answer-based conditions and a single-row Academic Applicability scope. QA found Academic Applicability confusing/unreliable in practical use and the UI only allowed one value per academic dimension. Candidate Profile Photo already had a system purpose but the final applicant preview showed only the uploaded file name rather than the image.

## Decision
1. Academic Applicability is evaluated from the application’s authoritative Admission Cycle context:
   Admission Cycle → Program Offering → Program Template → Degree → Degree Level, plus the Program Offering’s linked Curriculum.
2. Academic Applicability supports multiple values per dimension.
   - Values inside the same dimension are OR.
   - Configured dimensions are AND.
   - Leaving a dimension empty means Any.
3. Multiple selections are persisted using the existing `college_admission_form_field_scopes` table as equivalent scope combinations. No duplicate/new scope master is introduced and no migration is required.
4. University and College Field creation use a searchable multi-select Academic Applicability control. University scope supports Degree Level, Degree, Program and Curriculum. College scope additionally supports Program Offering and Admission Cycle.
5. Scope IDs are backend-validated against the correct University/College hierarchy and only current approved ACTIVE Curricula are accepted. Cross-dimension hierarchy mismatches are blocked.
6. Existing Edit Field operations preserve scope unless applicability inputs are explicitly submitted. University Edit Field exposes the multi-select applicability UI so existing scopes can be maintained safely.
7. Applicant runtime and submission validation continue to use the same server-side `CollegeAdmissionDynamicFieldService::isApplicable()` authority. Hidden/inapplicable required fields are not required and submitted values outside the current academic context are ignored/cleared.
8. `CANDIDATE_PROFILE_PHOTO` remains a system-purpose IMAGE field. On Final Preview, a newly selected profile image is rendered visually in the Applicant card using a local object URL; it is not duplicated as a plain filename in Application Details.

## Invariants
- The Admission Form does not ask the applicant to re-declare Degree/Program/Curriculum merely to resolve field visibility.
- Program Offering / Admission Cycle mapping is the authoritative runtime context.
- UI visibility and backend submission validation must agree.
- Academic Applicability does not replace answer-based conditions; applicability is evaluated first, then answer conditions.
- No external CSS or parallel design system is introduced.
- Existing form scope records remain compatible.

## QA Gate
1. Create a field with no Academic Applicability: visible for all mapped applications.
2. Create a field scoped to UG only: visible on UG mapped cycle, hidden on PG.
3. Select UG + PG in one dimension: visible for either matching level.
4. Select multiple Programs under one Degree: visible for either selected Program only.
5. Add a Curriculum restriction: only the Program Offering linked to that current approved Curriculum shows the field.
6. College scope: verify Offering/Cycle restrictions use the exact mapped Admission Cycle.
7. Verify a required scoped field is not required when hidden.
8. Attempt POST of an inapplicable field value: backend must not accept it as an effective answer.
9. Add Candidate Profile Photo system field, choose an image, go to Final Preview: image must render in Applicant card.
10. Confirm profile photo is not duplicated as a filename in Application Details.
