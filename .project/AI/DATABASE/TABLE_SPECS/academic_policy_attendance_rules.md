# Table Spec — academic_policy_attendance_rules

## Purpose
Stores the Attendance Policy configuration for one Academic Policy version. This is policy configuration only; it does not store student attendance.

## Relationship
- `academic_policy_id` -> `academic_policies.id`
- One row maximum per Academic Policy version.

## Fields
- `minimum_attendance_percent` — normal minimum attendance threshold.
- `calculation_level` — `COURSE`, `TERM`, or `OVERALL`.
- `allow_condonation` — whether a controlled shortage/condonation process is permitted.
- `condonation_minimum_percent` — lowest attendance percentage eligible to enter condonation processing; used only when condonation is allowed.
- `maximum_condonable_shortage_percent` — optional shortage ceiling; used only when condonation is allowed.
- `attendance_required_for_exam` — whether future exam eligibility must evaluate attendance.
- `allow_special_exemption` — whether medical/special attendance exemption processing is permitted.
- `rounding_rule` — `NONE`, `NEAREST`, `FLOOR`, or `CEIL`.
- `notes` — optional administrative guidance.
- audit ownership/timestamps: `created_by`, `updated_by`, timestamps.

## Integrity rules
- Minimum attendance must be 0–100.
- If condonation is disabled, condonation threshold fields are null/not used.
- If condonation is enabled, condonation minimum is required and must be lower than normal minimum attendance.
- Maximum condonable shortage, when supplied, must be 0–100 and cannot exceed normal minimum attendance.
- Editing is allowed only while the parent Academic Policy is an editable DRAFT.
- Any change clears the parent policy validation checkpoint.
