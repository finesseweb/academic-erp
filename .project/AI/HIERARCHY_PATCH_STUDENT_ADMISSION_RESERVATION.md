# Hierarchy Patch — Reservation to Student Lifecycle

Preserve the following implementation order:

Institution
→ Hierarchy
→ Roles / Permissions
→ Academic Setup
→ Approval
→ Admission Setup
→ Program Offering
→ Intake / Seat Capacity
→ Reservation / Quota Policy
→ Reservation / Seat Distribution
→ Merit / Roster / Selection Rules (configuration)
→ Student Admission Processing
   → Applications / Candidate Eligibility
   → Score Capture / Normalization
   → Interview Scheduling / Evaluation when required by Selection Rule
   → Merit / Roster Generation using the ACTIVE Selection Rule
   → Seat Allocation / Seat Consumption
   → Admission Confirmation
→ Student Lifecycle
→ Fees
→ Faculty
→ Course Offering
→ Faculty Allotment
→ Routine
→ Attendance
→ Internal Assessment
→ Examination
→ Result
→ Certificate / Degree
→ Reports
→ Integrations

Student Admission must not be implemented as an isolated module. It must
consume the already-approved Program Offering, active Intake, effective
Admission Seat Bucket, optional ACTIVE Reservation Plan, and exact ACTIVE
Selection Rule version. Interview, where configured, belongs inside Admission
Processing before Merit/Roster generation. Student Lifecycle begins only after
Admission Confirmation and must not own selection/interview scoring logic.


## Application / Candidate Eligibility implementation — 2026-08-26
Application header + ordered Program Choices are the first transactional Student Admission Processing records. Each submitted Program Choice locks the exact ACTIVE Selection Rule version plus its Intake seat bucket and optional Reservation context. Preliminary Candidate Eligibility is recorded per choice and must not execute Merit/Entrance/Interview thresholds. Score Capture / Normalization is the next implementation and must consume the locked Application Choice + Selection Rule version.

## Program Offering-scoped Admission Cycle correction — 2026-08-26
Admission Cycle must belong to an exact College Program Offering, not only to an Academic Session. The downstream chain is: `Program Offering -> Admission Cycle -> Application -> seat-bucket/specialization choice -> exact Selection Rule version -> score/interview/merit -> seat allocation`. Reservation remains optional per bucket.
