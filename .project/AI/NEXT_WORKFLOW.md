# Next Workflow — Applications / Candidate Eligibility QA

Selection Rules are the approved upstream policy configuration. Validate this Application milestone before Score Capture / Normalization begins:

1. Run migrations and confirm `college_admission_applications` and `college_admission_application_choices` exist.
2. Confirm `college_admission_application.*` permissions exist and are College-delegable.
3. Confirm Admission Cycle uses a searchable ACTIVE Program Offering selector and can be activated only when that exact offering has ACTIVE Intake + at least one eligible ACTIVE Selection Rule.
4. Confirm Reservation is optional for Admission Cycle activation; a bucket-level Reservation must be ACTIVE only where it exists.
5. Create an ACTIVE Admission Cycle for the exact Program Offering whose current date falls inside the Application Start / End window.
6. Open Applications / Candidate Eligibility and verify searchable Admission Cycle -> dependent Admission Seat Bucket filtering; Program Offering must be inherited from the selected cycle and must not be selected again.
7. Confirm only ACTIVE Offering + ACTIVE Intake + valid bucket + optional ACTIVE Reservation + ACTIVE Selection Rule contexts are available.
8. Create a DRAFT application with candidate name, DOB and one Program Choice.
9. Create another DRAFT with multiple ordered Program Choices and confirm duplicate bucket choices are rejected.
10. Edit a DRAFT and confirm submitted/withdrawn applications cannot be edited.
11. Submit a DRAFT inside the Cycle application window; confirm `submitted_at` is populated and each choice persists the exact ACTIVE Selection Rule ID/version.
12. Confirm submission outside the Cycle Application Start / End window is rejected.
13. Retire/replace a Selection Rule after an application is submitted and confirm the submitted choice remains linked to its historical rule version.
14. Mark one submitted choice ELIGIBLE, another INELIGIBLE, require an ineligibility reason, and confirm reset to PENDING.
15. Confirm preliminary eligibility does not calculate Merit/Entrance/Interview thresholds.
16. Confirm cross-College Cycle/Application/Choice IDs are rejected/404.
17. Confirm audit events are written for create, update, submit, eligibility change and withdraw.
18. Confirm Test Data Cleanup can delete an Application + Choices when no downstream records exist.
19. Run Full Academic Test Reset and confirm child-first order: Application Choices -> Applications -> Selection Rule tie-breakers -> Selection Rules -> Admission Cycles -> Reservation -> Intake -> Offering.
20. Run frontend production build and verify the wide dialog, searchable selectors, long labels and responsive layout at desktop/tablet/mobile widths.
21. Owner reviews and accepts this milestone.

After owner acceptance, the next implementation is **Score Capture / Normalization**. It must attach score data to the submitted Application Choice and consume its locked Selection Rule version. Interview Scheduling / Evaluation remains later and is required only when Interview weight > 0.
