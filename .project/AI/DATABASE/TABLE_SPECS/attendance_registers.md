# Table Spec — attendance_registers

One operational attendance register per `class_schedules.id`. Stores the resolved `academic_policy_id`, lifecycle (`DRAFT|FINALIZED`), revision counter, finalization actor/time, correction reason and audit ownership timestamps. The Academic Policy relationship is a historical reference; thresholds remain owned by `academic_policy_attendance_rules`.

Key constraints/indexes: unique `class_schedule_id`; RESTRICT Class Schedule and Academic Policy deletion; `(academic_policy_id,status)` index.
