# Table Spec — academic_policy_credit_category_requirements

Dynamic Course Category completion thresholds for one Academic Policy version.

## Relationships
- `academic_policy_id -> academic_policies.id` ON DELETE CASCADE
- `course_category_id -> course_categories.id` ON DELETE RESTRICT

## Columns
- `id`
- `academic_policy_id`
- `course_category_id`
- `minimum_credits` — mandatory when a requirement row exists
- `maximum_credits` — optional; when supplied it must be >= minimum
- `display_order`
- `created_by`, `updated_by`, timestamps

## Business rules
- Same Course Category cannot appear twice in one Academic Policy version.
- The Course Category must be ACTIVE and belong to the same University as the Academic Policy.
- There is no `required` flag. If a category row exists, its minimum credit threshold is part of completion eligibility.
- If a category has no policy-level minimum, do not add a requirement row.
- Categories come from Course Category Master, so institutions can add Research, Field Work, Dissertation, Community Engagement, etc. without schema changes.
