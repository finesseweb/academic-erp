# Current Implementation State Patch — Score Capture / Normalization

## 2026-08-26
Score Capture / Normalization implemented and **OWNER_QA_REQUIRED**.

Implemented:
- normalized Merit/Qualifying and Entrance raw/max score capture
- exact locked Selection Rule linkage
- component threshold evaluation
- final weighted score when all weighted components are available
- PENDING_INTERVIEW handoff when Interview is weighted
- College permissions/routes/sidebar/page
- Test Data Cleanup + Full Academic Reset integration

Do not start Merit / Roster Generation until Score Capture QA is accepted. If Interview is required by a test rule, Interview Scheduling / Evaluation is the next mandatory implementation before Merit generation.
