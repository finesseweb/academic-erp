# Table Specification: Universities

## Identity
- Physical table / Eloquent model: `universities` / `University`.
- Domain/scope/grain: University Foundation; exactly one root University row for the current product deployment.
- Purpose: official University identity, contact, address, country, and timezone.

## Keys, Relationships, and Indexes
- Unsigned BIGINT `id` primary key; unique bounded `code` is the stable business identifier.
- No College foreign key: the University is the organizational root.
- Affiliated College relationship is deferred to the next approved milestone.

## History and Security
- Normal updates preserve timestamps and append `UNIVERSITY_UPDATED` to `audit_logs` in the same transaction.
- No hard-delete UI or route exists.
- Reads require `university.view`; updates require `university.update`, both at `UNIVERSITY` / `university` scope.

## Change History
- 2026-08-18: Created by `2026_08_18_120000_create_university_profile_foundation`.
