# Table Specification: Audit Logs

## Identity
- Physical table / Laravel migrations / Eloquent model: `audit_logs` / `AuditLog`
- Domain/grain: Audit/System; one immutable row per security or high-impact event
- Expected growth: high; retention/archive policy required before production scale

## Keys and Columns
- PK unsigned BIGINT.
- Optional actor FK uses SET NULL so events survive account removal.
- Action, resource identity, optional paired scope, request ID, IP/user-agent, safe JSON metadata and `created_at`.
- No `updated_at` by design because records are immutable.

## Indexes / Access
- Actor/date, action/date, resource/date, scope/date and date indexes support audit filters and retention scans.
- Scope pair check prevents partial scope values.

## Security / History
- Append-only; no normal update/delete API.
- Never store passwords, password hashes, session/JWT/reset tokens, secrets or full sensitive payloads in metadata.
- Access requires `audit.view` plus scope enforcement.

## Change History
- 2026-08-13: Created; seed records `IDENTITY_FOUNDATION_SEEDED`.
- 2026-08-13: Authentication lifecycle events implemented.
- 2026-08-18: Repository foundation created for University Profile. Current milestone stores `event`, resource type/id, safe JSON `before`/`after`, actor, IP, and immutable `created_at`; `UNIVERSITY_UPDATED` is written transactionally. Broader audit-list fields remain subject to the ordered Audit Logs milestone.
