# Current Implementation State Patch — Interview Scheduling / Evaluation

## 2026-08-27
Interview Scheduling / Evaluation implemented and **OWNER_QA_REQUIRED**.

Implemented:
- interview-required choices only: SUBMITTED + ELIGIBLE + Score Capture exists + locked Interview weight > 0
- schedule, venue/mode and panel name
- ACTIVE College-user evaluator assignment with immutable evaluator-name snapshot
- per-evaluator raw/max score capture and backend 0-100 normalization
- final Interview component = average of evaluator normalized scores
- exact locked Selection Rule linkage
- completed Interview writes normalized score back to `college_admission_scores` and triggers final qualification/weighted-score re-evaluation
- minimum Interview and minimum final weighted score remain enforced by the locked Selection Rule
- College permissions/routes/sidebar/page and audit history
- Full Academic Test Reset child-first deletion: evaluator rows -> Interview -> Score -> Application Choice -> Application

Status: OWNER_QA_REQUIRED.

Do not start Merit / Roster Generation until Interview QA is accepted for an Interview-weighted rule. For rules with Interview weight 0, Score Capture QUALIFIED results remain directly merit-ready.
