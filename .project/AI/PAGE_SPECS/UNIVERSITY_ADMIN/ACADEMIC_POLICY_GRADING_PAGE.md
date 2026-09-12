# Academic Policy — Grading Rules
Status: IMPLEMENTED — Phase 4

## Purpose
Defines how final percentage/result values are converted into grades/grade points and whether SGPA/CGPA are calculated.

## Dynamic design
Grade codes are data, not code. Universities may create A+, A, O, S, Distinction, Pass, etc. without a migration.

## Fields
- Grading Basis: Letter Grade / Grade Point / Pass-Fail
- Maximum Grade Point
- Dynamic Grade Bands: Min %, Max %, Grade Code, Label, Grade Point, Passing?
- SGPA enabled + decimal places
- CGPA enabled + decimal places
- Rounding rule
- Notes

Grade bands may not overlap. Component marks still come from future Assessment Scheme; this policy converts evaluated outcomes into grading values.
