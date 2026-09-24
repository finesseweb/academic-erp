# Internal Assessment — Quizzes

Status: IMPLEMENTED — OWNER QA REQUIRED

Route: `/college/{college}/internal-assessment/quizzes`

Permissions: `college_internal_assessment.view`, `college_internal_assessment.quiz`.

Quizzes require an ACTIVE Quiz component, same-Course ACTIVE Faculty Allocation, governed open/close timestamps, and duration. DRAFT → PUBLISHED → CLOSED is enforced. Publication snapshots the exact canonical Enrollment roster under the Faculty Allocation Batch/Section and Curriculum Course Mapping scope. Question banks and online attempt engines are outside this milestone.
