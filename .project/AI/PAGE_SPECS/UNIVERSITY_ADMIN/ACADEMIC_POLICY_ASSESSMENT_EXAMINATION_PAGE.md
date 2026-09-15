# Academic Policy — Assessment / Examination Rules

Status: IMPLEMENTED — Academic Policy Phase 3

## Purpose
Defines general assessment/examination evaluation permissions and pass controls for an Academic Policy version. It does not create exams, assessment components, schedules, marks, results, supplementary attempts or improvement attempts.

## Route
- GET `/admin/academic-policies/{academicPolicy}/assessment-examination`
- PUT `/admin/academic-policies/{academicPolicy}/assessment-examination`

## Fields
- Minimum Overall Pass % — optional generic overall threshold.
- Require Separate Assessment Component Pass — whether future result evaluation must separately evaluate configured assessment components.
- Absence Result — FAIL / INCOMPLETE / AS_PER_EXAM_RULE.
- Allow Grace Marks — policy permission only.
- Maximum Grace Marks — required when grace is allowed.
- Allow Supplementary Examination — policy permission only.
- Allow Improvement Examination — policy permission only.
- Notes.

## Important anti-hard-coding rule
Do not add hard-coded Mid Semester, Internal, External, Practical, Viva, Assignment or Quiz columns to Academic Policy. Assessment components, marks, weightage and component-specific pass thresholds belong to the future configurable **Assessment Scheme / Examination Structure**. When that master exists, component-level policy rules must reference its IDs rather than duplicate component names in Academic Policy.

## Future consumer relationship
`Academic Policy → Assessment Scheme → Marks/Exam Processing → Result Evaluation`

If `require_separate_component_pass = true`, the future result engine must evaluate each applicable configured component requirement. Phase 3 stores the policy instruction only; it does not invent component definitions before the Assessment Scheme exists.

Grace, supplementary and improvement toggles permit later controlled workflows; they never automatically alter marks or create attempts.
