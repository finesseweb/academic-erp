# `authorized_signatories`

## Purpose
Stores University-owned appointments for people authorized to sign governed records. A record is independent of a login user and is retained when inactive.

## Columns
- `id`: primary key
- `university_id`: required FK to `universities.id`, delete restricted
- `full_name`: official signatory name
- `designation`: appointment designation
- `authority_type`: controlled signing category
- `email`, `phone`: optional official contact details
- `effective_from`, `effective_until`: appointment date range
- `status`: `ACTIVE` or `INACTIVE`
- `notes`: optional administrative notes
- `created_at`, `updated_at`: lifecycle timestamps

## Relationships and Grain
Many signatories belong to one University. Grain: one row per governed signatory appointment and authority category.

## Indexes
- (`university_id`, `status`, `effective_from`)
- (`university_id`, `authority_type`, `status`)

## Safety Rules
The end date cannot precede the start date. No hard-delete workflow is exposed. Signature specimen data is absent until protected document-storage policy is approved.
