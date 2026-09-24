# Medical / Special Attendance Exemption

Status: IMPLEMENTED — OWNER QA REQUIRED

Route: `/college/{college}/attendance-eligibility`

Permissions: `college_attendance_exception.view`, `college_attendance_exception.request`, `college_attendance_exception.decide`.

Medical/special exemption requests are permitted only when the resolved Attendance Rule enables them. Each request records the Student Enrollment, Course Offering anchor, Academic Policy, finalized attendance percentage, reason and optional supporting reference. Approval changes final eligibility only; it never changes held/attended counts or Attendance Records. Decisions are College-scoped and audited.
