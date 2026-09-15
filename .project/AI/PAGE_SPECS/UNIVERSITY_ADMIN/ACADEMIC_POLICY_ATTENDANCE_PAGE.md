# Academic Policy Attendance Page

## Purpose
Configure attendance regulations inside an Academic Policy version. This page defines rules for future Attendance and Examination modules; it does not process student attendance itself.

## Route contract
- GET `/admin/academic-policies/{academicPolicy}/attendance`
- PUT `/admin/academic-policies/{academicPolicy}/attendance`

## Permissions
- View: `academic_policy.view`
- Update: `academic_policy.update`
- Parent policy must also be an editable DRAFT and not SUBMITTED / UNDER_APPROVAL / APPROVED.

## Sections
### Attendance Requirement
- Minimum Attendance %
- Calculation Level: Course-wise / Term-Semester-wise / Overall
- Rounding Rule: None / Nearest / Floor / Ceil

### Condonation / Shortage
- Allow Attendance Condonation
- Condonation Minimum Attendance % (required when condonation is allowed)
- Maximum Condonable Shortage % (optional)

Condonation permission does not automatically approve a student. Future student-level processing must verify and approve the request under the applicable workflow.

### Eligibility & Exceptions
- Attendance Required for Exam Eligibility
- Allow Medical / Special Exemption

Special Exemption only permits a future controlled exemption workflow; it does not grant an exemption automatically.

### Notes
Optional administrative guidance.

## Validation integration
Attendance fields are included in the Academic Policy fingerprint. Saving any Attendance Policy change clears the previous policy validation checkpoint.

## Future consumption
Later modules must consume this policy instead of hard-coding thresholds:
- Student Attendance
- Attendance Shortage / Condonation
- Medical / Special Exemption
- Examination Eligibility

Scope resolution remains: Curriculum-specific rule -> Program-specific rule -> University-wide fallback.

## Future consumer workflow contract

The Attendance Policy is a configuration source. Student-level processing is **not implemented in Academic Policy Phase 2**.

Future Attendance must resolve the applicable policy by scope and use these settings as follows:

- `minimum_attendance_percentage`: normal threshold.
- `calculation_level`: determines whether eligibility is evaluated course-wise, term-wise, or overall.
- `condonation_allowed`: permits or blocks a future condonation workflow.
- `condonation_minimum_percentage` / `maximum_condonable_shortage_percentage`: limits eligibility to request condonation; they do not automatically grant it.
- `medical_special_exemption_allowed`: permits or blocks a future controlled special-exemption workflow; it never grants an exemption automatically.
- `attendance_required_for_exam_eligibility`: tells the future Examination eligibility engine whether final attendance eligibility must be satisfied.

### Planned student flow

```text
Student Attendance
→ Calculate Attendance
→ Compare with Resolved Attendance Policy
→ Satisfied? YES → Attendance Eligible
→ Satisfied? NO  → Check Condonation Eligibility
                 → or Medical/Special Exemption Eligibility
                 → Request + Verification + Approval
                 → Final Attendance Eligibility
→ Examination Eligibility consumes final result when configured.
```

### Data integrity rule

Raw held/attended counts and calculated percentages remain unchanged after condonation or exemption. Approved exceptions must be stored as separate auditable decisions.
