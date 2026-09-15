# Current Implementation State Patch — Admission Form Advanced Field Rules

Implemented reusable Admission Form field constraints and behavior:

- Text/textarea/email/phone: min, max, exact character length.
- Number: min/max value, whole-number-only, decimal-place limit.
- Cross-field Number/Date comparison using LT/LTE/GT/GTE/EQ/NEQ.
- Generic copy-from-field behavior driven by another field value, including read-only target behavior.
- Builder UI available in University Base and College Extension DRAFT forms, including Edit Field.
- Public Applicant Form applies live copy behavior and client constraints; backend revalidates all rules.
- Internal College Admission Application form also applies live copy behavior and client constraints; backend revalidates all rules.
- Referenced comparison/copy source fields cannot be deleted while depended upon.

Migration required: `2026_09_01_071500_add_cross_field_validation_and_copy_rules_to_admission_form_builder.php`.
