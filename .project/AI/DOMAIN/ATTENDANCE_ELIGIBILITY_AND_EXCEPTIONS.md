# Attendance Eligibility & Exceptions — Future Integration Contract

Status: **PLANNED downstream workflow**. Policy configuration exists; student-level processing does not yet exist.

## Purpose

Keep one permanent contract between Academic Policy, Attendance and Examination so later implementation does not duplicate or bypass policy rules.

## Source of rules

The future Attendance module must resolve the applicable **approved/current Academic Policy** using the project's scope-resolution rules. The resolved Attendance Policy provides minimum percentage, calculation level, condonation controls, special-exemption permission, rounding rule and whether attendance affects examination eligibility.

## Student processing

```text
Published Class Sessions
→ Attendance Entry
→ Approved Corrections
→ Attendance Calculation
→ Policy Resolution
→ Shortage Evaluation
   ├── Meets threshold → Attendance Eligible
   ├── Condonation path (only if policy permits)
   │   → Request → Verification → Approval/Rejection → History
   └── Medical/Special Exemption path (only if policy permits)
       → Request → Documents → Verification → Approval/Rejection → History
→ Final Attendance Eligibility
→ Examination Eligibility (only if policy requires attendance)
```

## Non-negotiable rules

1. Policy toggles permit a workflow; they never grant a student exception automatically.
2. Condonation must obey configured minimum/shortage limits.
3. Medical/Special Exemption requires a controlled decision and supporting evidence where configured.
4. Raw attendance is immutable through exception decisions. Do not change classes held, classes attended, or calculated percentage to make a student eligible.
5. Store the exception decision separately with requester, verifier/approver, timestamps, reason, documents/references and audit history.
6. Examination must consume the final Attendance Eligibility result rather than reimplementing attendance calculations.
7. If attendance is not required for examination eligibility under the resolved policy, low attendance may still be reported but must not independently block the exam.
8. Historical decisions must remain tied to the policy/version effective for that student's academic context.

## Ownership

- **Academic Policy:** defines rules and permissions.
- **Attendance:** records attendance, calculates percentage, evaluates shortage and manages exception workflows.
- **Generic Approval Engine:** controls configured approval stages for condonation/exemption where required.
- **Examination:** consumes final eligibility; does not mutate attendance.
- **Audit:** preserves all decisions and changes.
