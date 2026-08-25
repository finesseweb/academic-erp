# Next Workflow — College Program Offerings QA

Academic Calendar QA and owner review are complete. Validate the first College Academic Setup milestone:

1. Run migrations and confirm `college_program_offerings` exists.
2. Confirm `college_program_offering.*` permissions exist and are College-delegable.
3. Assign the required permissions to a College role and confirm the scoped College user sees `College Academic Setup -> Program Offerings`.
4. Create an offering with a same-University active Program Template, PLANNED/ACTIVE Academic Session and approved ACTIVE matching Curriculum.
5. Confirm the new offering starts `INACTIVE`.
6. Activate the offering and confirm the status becomes `ACTIVE`.
7. Attempt a duplicate for the same College + Program + Academic Session and confirm it is rejected.
8. Attempt to submit a Curriculum that does not match the selected Program or Academic Session and confirm backend rejection.
9. Attempt cross-University IDs and confirm rejection.
10. Deactivate/re-activate and confirm records are preserved and explicit audit events are written.
11. Confirm an inactive College can view but cannot create/update/change status.
12. Confirm unauthorized College users receive 403 and the sidebar item is hidden.
13. Run frontend production build and verify responsive behavior.
14. Owner reviews before Intake / Seat Capacity begins.

Important: Intake / Seat Capacity is intentionally not part of Program Offerings.
