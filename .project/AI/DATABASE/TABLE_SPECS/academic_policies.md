# Table Spec — academic_policies

Versioned University Academic Policy header.

Key relations:
- university_id -> universities
- academic_session_id -> academic_sessions
- program_template_id -> program_templates (nullable by scope)
- curriculum_id -> curricula (nullable by scope)
- parent_policy_id -> academic_policies (revision lineage)
- superseded_by_id -> academic_policies (replacement pointer)

Lifecycle:
- DRAFT / ACTIVE / RETIRED
Approval state:
- NOT_SUBMITTED / SUBMITTED / UNDER_APPROVAL / RETURNED / REJECTED / APPROVED

Phase 1 uses DRAFT management and validation. Approval engine wiring is the next gate.
