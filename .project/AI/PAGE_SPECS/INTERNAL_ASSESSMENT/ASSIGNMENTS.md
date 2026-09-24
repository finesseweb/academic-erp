# Internal Assessment — Assignments

Status: IMPLEMENTED — OWNER QA REQUIRED

Route: `/college/{college}/internal-assessment/assignments`

Permissions: `college_internal_assessment.view`, `college_internal_assessment.assignment`.

Assignments require an ACTIVE Assignment component and ACTIVE Faculty Allocation for the same Course Offering. Open/due timestamps must fall within the active Academic Calendar period for the exact Curriculum Term. Publishing transactionally freezes the exact canonical Enrollment roster; closing is terminal. Marks Entry is a later milestone.
