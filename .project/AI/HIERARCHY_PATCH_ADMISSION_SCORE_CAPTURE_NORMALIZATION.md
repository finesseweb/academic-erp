# Hierarchy Patch — Admission Score Capture / Normalization

Preserved implementation sequence:

Program Offering -> Intake / Seat Bucket -> optional Reservation -> Selection Rule -> Admission Cycle -> Application -> Candidate Eligibility -> **Score Capture / Normalization** -> Interview Scheduling / Evaluation (when required) -> Merit / Roster Generation -> Seat Allocation / Seat Consumption -> Admission Confirmation -> Student Lifecycle.

Score Capture must consume the Application Choice's locked Selection Rule version. Interview belongs to Admission processing, not Student Lifecycle.
