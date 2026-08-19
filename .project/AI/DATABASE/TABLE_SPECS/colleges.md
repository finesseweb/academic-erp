# Table Specification: Affiliated Colleges

## Identity
- Physical table / Eloquent model: `colleges` / `College`.
- Domain/grain: University Foundation; one row per College affiliated with the single root University.
- Scope and ownership: University-owned child through required `university_id`.

## Keys and Columns
- Unsigned BIGINT `id` primary key; globally unique stable `code`.
- Identity: name, affiliation type, active/inactive status.
- Contact/address: official email/phone/website, postal fields, country, timezone.
- Optional `principal_user_id` references a login account when the later assignment workflow exists; no free-text principal identity is duplicated.

## Relationships and Indexes
- `university_id -> universities.id`, required many-to-one, RESTRICT delete.
- `principal_user_id -> users.id`, optional many-to-one, SET NULL delete.
- (`university_id`, `status`, `name`) supports University lists filtered by lifecycle status.
- (`university_id`, `affiliation_type`, `name`) supports affiliation filtering and ordered lists.

## History and Security
- Normal deletion is not implemented. Lifecycle uses ACTIVE/INACTIVE status.
- Create, update, and status changes append immutable audit events transactionally.
- University-scoped permissions: `college.view`, `college.create`, `college.update`, `college.disable`.

## Change History
- 2026-08-19: Created by `2026_08_19_090000_create_affiliated_colleges`.
