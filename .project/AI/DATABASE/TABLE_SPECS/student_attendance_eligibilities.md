# `student_attendance_eligibilities`

Final Attendance eligibility snapshot consumed by downstream Examination.

Unique business key: Student Enrollment + Course Offering. Required relationships: College, Student Enrollment, Course Offering and Academic Policy. The row snapshots classes held/attended, rounded percentage, eligibility boolean, basis and finalization actor/time. Re-finalization updates the same result after authoritative attendance or exception changes; it never mutates source Attendance Records.
