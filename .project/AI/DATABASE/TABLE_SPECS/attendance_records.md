# Table Spec — attendance_records

Stores one raw status (`PRESENT|ABSENT|LATE|EXCUSED`) per canonical Student Enrollment per Attendance Register, with optional remarks and audit ownership timestamps.

Key constraints/indexes: unique `(attendance_register_id,student_enrollment_id)`; both parent relationships use RESTRICT; `(student_enrollment_id,attendance_status)` supports student/course attendance projections.
