# Attendance Condonation

Status: IMPLEMENTED — OWNER QA REQUIRED

Route: `/college/{college}/attendance-eligibility`

Permissions: `college_attendance_exception.view`, `college_attendance_exception.request`, `college_attendance_exception.decide`.

Condonation is available only for finalized attendance below the normal threshold and within the resolved policy's minimum attendance and maximum shortage limits. Requests snapshot the percentage and Academic Policy, require a reason, and remain separate from raw attendance. College-scoped authorized decision makers approve or reject with remarks; all mutations are audited.
