# Internal Assessment — Marks Entry

Status: IMPLEMENTED — OWNER QA REQUIRED

Route: `/college/{college}/internal-assessment/marks`

Permissions: `college_internal_assessment.view`, `college_internal_assessment.marks_entry`.

Marks Entry lists PUBLISHED/CLOSED activities and their immutable publication roster. Complete-roster save supports ENTERED marks or ABSENT, validates maximum marks from the snapshotted component relationship, preserves optional remarks, increments revision on correction, and audits each record. Approval/finalization are not bypassed by this milestone.
