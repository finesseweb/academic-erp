# ADR 039 - Applicant Email Verification Trait and Optional Seat Selection

Date: 2026-08-31

## Decision

1. The ERP User model implements Laravel's `MustVerifyEmail` contract and uses the framework trait from `Illuminate\Auth\MustVerifyEmail`. This keeps applicant email verification on the same User identity used later for Student access.
2. Public applicant seat/category selection is optional per College Admission Form Mapping.
3. `seat_selection_required` defaults to `false`. A normal applicant can therefore submit the mapped application without selecting an Intake seat bucket.
4. A College may explicitly enable seat selection on a mapping when that admission process genuinely requires the applicant's form to be tied to a seat/category at submission time.
5. Disabling seat selection does not remove Program Offering + Admission Cycle mapping. Those remain the academic scope of the application. Seat allocation/selection can occur later in the admission workflow.

## Reason

Application submission and seat allocation are separate business concepts. Many institutions collect applications first and apply seat/category rules later. The public form must not force a seat decision unless the College configures that requirement.
