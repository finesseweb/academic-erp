# `attendance_exception_requests`

College-scoped, student-level controlled Attendance exception workflow.

Key relationships: College, Student Enrollment, Course Offering and snapshotted Academic Policy are required; requesting/deciding Users are nullable historical actors. `type` is `CONDONATION` or `SPECIAL_EXEMPTION`; status is `PENDING`, `APPROVED` or `REJECTED`. Percentage, reason, optional supporting reference, decision remarks and decision time preserve the decision basis. Indexed by College queue and subject/type/status. Rows are history and are not hard-deleted by normal business flows.
