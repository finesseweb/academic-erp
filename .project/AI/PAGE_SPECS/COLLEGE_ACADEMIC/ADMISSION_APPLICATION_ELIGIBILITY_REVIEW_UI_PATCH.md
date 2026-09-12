# Admission Application Eligibility Review UI Patch

## Rule
Candidate eligibility is reviewed per submitted Admission Application Choice / preference.

Lifecycle:

`DRAFT -> SUBMITTED -> Preference Eligibility Review (PENDING / ELIGIBLE / INELIGIBLE)`

- A DRAFT preference remains `PENDING` and must not be assessed yet because the candidate can still edit the application choices.
- The UI must still show an explicit **Eligibility Review** area on DRAFT cards so the workflow is discoverable. Eligible/Ineligible actions are visible but disabled with guidance to submit first.
- After the application is `SUBMITTED`, authorized users can mark each preference **ELIGIBLE** or **INELIGIBLE** independently.
- `INELIGIBLE` requires a reason.
- A reviewed preference can be reset to `PENDING` while no downstream score/interview/merit processing prevents it.
- Later Score / Entrance / Interview / Merit modules must consume only submitted choices that are `ELIGIBLE`.
- Eligibility remains separate from Selection Rule score thresholds; threshold evaluation belongs to Score/Merit processing.
