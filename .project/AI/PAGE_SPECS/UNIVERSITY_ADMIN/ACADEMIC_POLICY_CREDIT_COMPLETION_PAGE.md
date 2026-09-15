# Academic Policy — Credit / Completion

## Purpose
First rule section under Academic Policies, matching the frozen hierarchy item `Credit / Completion Policy [optional where required]`. It defines program-completion thresholds without hard-coding academic category names.

## Routes
- `GET /admin/academic-policies/{academicPolicy}/credit-completion`
- `PUT /admin/academic-policies/{academicPolicy}/credit-completion`

## General Completion fields
- Minimum Total Credits
- Minimum Completion CGPA
- Maximum Program Duration (months)
- Allow Credit Transfer
- Maximum Credit Transfer %
- Allow Credit Exemption
- Notes

## Dynamic Credit Category Requirements
Each row contains:
- Course Category — selected from ACTIVE Course Category Master records owned by the same University
- Minimum Credits — required for the row
- Maximum Credits — optional
- Display Order

No Major/Minor/Skill/etc. field is hard-coded. A new University Course Category automatically becomes available for policy configuration without database changes.

## Meaning of a row
If a Course Category requirement row exists, its Minimum Credits must be satisfied for completion. If the institution has no minimum threshold for a category, no row is created. There is intentionally no Required Yes/No flag.

## Validation
- Same Course Category cannot be added twice to one policy version.
- Minimum Credits must be greater than zero.
- Optional Maximum Credits must be greater than or equal to Minimum Credits.
- Sum of configured Course Category minimum credits cannot exceed Minimum Total Credits when Minimum Total Credits is configured.
- Maximum Credit Transfer % is required when Credit Transfer is enabled.
- Maximum Credit Transfer % must be 0–100.
- CGPA must be 0–10.
- This section is optional; absence produces a warning, not a validation failure.
- Any change to general rules or category rows invalidates the previous policy validation checkpoint.

## Locking
Rules are editable only while the parent Academic Policy is an editable DRAFT.

## Future use
Student completion eligibility will later compare earned credits by Course Category against these dynamic requirements, then apply total credits/CGPA/duration rules through the Academic Policy scope-resolution hierarchy.


## Future Student-Level Consumption — Frozen Rule
- `Allow Credit Transfer` and `Allow Credit Exemption` are policy permissions only; they do not themselves modify a student's credits.
- The student operation is intentionally deferred to **Student Academic Lifecycle**.
- Planned flow: Student -> Credit Transfer/Exemption Request -> Supporting Documents -> Academic Equivalence/Verification -> Approval -> Credit Recognition -> Student Credit Ledger.
- Maximum Credit Transfer % must be enforced when the future student-level transfer workflow is implemented.
- The future Credit Ledger must distinguish earned, transferred, and exempted/recognized credits so completion checks remain auditable.
- This future workflow must consume the ACTIVE applicable Academic Policy; it must not hard-code transfer/exemption permission in the student module.
