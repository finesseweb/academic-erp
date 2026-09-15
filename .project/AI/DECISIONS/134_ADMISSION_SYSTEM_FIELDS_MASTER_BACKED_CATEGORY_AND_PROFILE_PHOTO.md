# ADR 134 — Admission System Fields: Master-backed Candidate Category and Profile Photo

## Decision
Admission Form fields remain fully placeable inside any Step and optional Panel, but fields that feed downstream ERP logic may carry a stable `system_purpose` independent of label, field key, step, or panel.

Supported first-class purposes:
- `CANDIDATE_RESERVATION_CATEGORY`
- `CANDIDATE_PROFILE_PHOTO`

## Reservation Category
- University may label the field `Caste Category`, `Social Category`, `Reservation Category`, etc.
- It must use Dropdown input.
- Applicant choices come from the University's ACTIVE Reservation Category Master; SC/ST/OBC/EWS/Open labels are not hard-coded in the form.
- On College form mapping, an effective system-purpose category field is auto-mapped when no explicit override is chosen.
- Seat Allocation still separates candidate category from the seat bucket consumed.
- Scholarship eligibility consumes the candidate category, never the consumed seat bucket.

## Candidate Profile Photo
- University may label it `Applicant Photograph`, `Profile Photo`, etc.
- It must use Image input.
- Required/Optional and Step/Panel placement remain configurable.
- The system purpose makes the uploaded image available to later Admission/Student Profile workflows without relying on label or field key.

## Placement
Changing Panel/Section or display order never changes the system meaning. System-purpose uniqueness is enforced per University template.
