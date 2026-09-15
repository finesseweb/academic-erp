# college_admission_interviews

One operational Interview record per submitted Admission Application Choice when its locked Selection Rule has Interview weight > 0.

Relationships:
- belongs to `college_admission_applications`
- belongs to `college_admission_application_choices` (unique)
- belongs to exact locked `college_admission_selection_rules`
- has many `college_admission_interview_evaluators`

Lifecycle: `SCHEDULED | COMPLETED | CANCELLED`.
Completed rows store the final normalized Interview component used by the shared Admission score context. Historical rule linkage must never be silently relinked.
