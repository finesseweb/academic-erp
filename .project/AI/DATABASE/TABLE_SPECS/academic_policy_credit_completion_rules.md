# Table Spec — academic_policy_credit_completion_rules

One-to-one optional **general** Credit / Completion rule section for an Academic Policy.

## Primary relationship
- `academic_policy_id -> academic_policies.id` ON DELETE CASCADE

## Canonical fields
- `minimum_total_credits`
- `minimum_completion_cgpa`
- `maximum_program_duration_months`
- `allow_credit_transfer`
- `maximum_credit_transfer_percent`
- `allow_credit_exemption`
- `notes`
- audit ownership/timestamps

## Important rule
Course-category-specific requirements are **not stored as columns in this table**. Major, Minor, Skill, Internship, etc. are not hard-coded. They are stored dynamically in `academic_policy_credit_category_requirements` using the existing Course Category Master.
