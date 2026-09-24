# Examination Attendance Eligibility

Status: IMPLEMENTED — OWNER QA REQUIRED

Route: `/college/{college}/attendance-eligibility`

Permissions: `college_attendance_eligibility.view`, `college_attendance_eligibility.finalize`.

The page evaluates finalized attendance at the resolved policy calculation level (Course, Term or Overall), applies the configured rounding and threshold, and then considers an approved condonation or special exemption. If attendance is not required for Examination, the result is eligible with `NOT_REQUIRED` basis. Finalization persists an auditable snapshot keyed by Student Enrollment + Course Offering for downstream Examination consumption. Re-finalization refreshes that snapshot; raw attendance remains unchanged.
