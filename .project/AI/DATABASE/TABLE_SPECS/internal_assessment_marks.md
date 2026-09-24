# `internal_assessment_marks`

One mutable pre-approval marks-entry record per `internal_assessment_activity_students` row. Stores ENTERED/ABSENT, nullable marks, remarks, revision number and latest entry actor/time. Maximum marks and pass threshold remain authoritative on the parent Component. Corrections update the same row with audit history; later approval/finalization controls locking.
