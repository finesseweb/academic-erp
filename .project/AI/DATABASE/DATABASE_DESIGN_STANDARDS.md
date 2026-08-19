# Database Design Standards

This document defines how the College ERP database must be designed so that humans and AI agents can understand, query and extend it safely.

## 1. Design from Business Entities, Not Screens
Do not create one table per page. A page is a UI view; tables represent business entities and relationships.

Example: Student List, Student Profile, Attendance Entry and Student Report may all use the same student/master/academic relationship tables rather than four page-specific tables.

Before designing a table, identify:
- Business entity.
- Source of truth.
- Tenant/College owner.
- Lifecycle.
- Relationships.
- Expected reads and writes.
- Reporting requirements.

## 2. Naming
Use consistent `snake_case` physical table and column names unless an existing legacy table must be preserved.

Preferred examples:
- `students`
- `academic_sessions`
- `student_enrollments`
- `course_offerings`
- `attendance_records`

Foreign-key columns should clearly identify the referenced entity, e.g. `student_id`, `college_id`, `course_id`.

When mapping legacy names, document the legacy physical name and logical entity name in `SCHEMA_CATALOG.md` and its table specification.

## 3. Primary Keys
- Every operational table must have a stable primary key.
- Prefer numeric `BIGINT UNSIGNED`/Laravel migrations / Eloquent `BigInt` for high-growth tables when designing new schema; use `INT UNSIGNED` when scale clearly permits and project convention requires it.
- Never expose a primary key as proof of authorization.
- Mapping/junction tables may use a composite unique key where appropriate, while still having a surrogate ID if operationally useful.

## 4. Multi-Tenant Ownership
College ERP is multi-tenant. Tenant-scoped records must have an explicit, verifiable ownership path to the University/Affiliated College.

Prefer a direct `college_id` on high-volume or security-sensitive tenant tables when it simplifies authorization and query performance, even if ownership could technically be inferred through several joins. Avoid inconsistent duplicated ownership values: when denormalized ownership is stored, enforce and test it.

Every table spec must state one of:
- Global table.
- Tenant table with direct `college_id`.
- Tenant table with documented ownership path.

## 5. Data Types
Choose the smallest correct type without sacrificing future-safe range.
- IDs: integer types consistent with referenced PK.
- Money: `DECIMAL`, never floating point.
- Boolean: Laravel migrations / Eloquent `Boolean` / MySQL-compatible boolean representation.
- Date only: `DATE`.
- Date/time: `DATETIME`/`TIMESTAMP` according to project timezone policy.
- Free text: `TEXT` only when variable long text is required.
- Short labels/codes: bounded `VARCHAR` with realistic length.
- JSON: only for genuinely flexible/auxiliary structures; do not hide relational data in JSON.

Use one character set/collation policy consistently across related tables. New Unicode text should support the required languages.

## 6. Nullability and Defaults
- `NOT NULL` should be the default for mandatory data.
- Nullable columns must have a documented business meaning.
- Do not use magic sentinel values such as `0`, `-1`, `N/A` instead of proper NULL/reference semantics unless a legacy constraint requires it.
- Defaults must represent a valid business default, not merely make an insert succeed.

## 7. Keys and Constraints
Use database constraints for durable integrity, not only application validation.
Consider:
- Primary keys.
- Foreign keys.
- Unique constraints.
- Composite uniqueness.
- Check constraints where supported/appropriate.

Examples of business uniqueness might include:
- A college code globally unique.
- A roll number unique within a defined College/session scope.
- One enrollment per student + program/session combination if business rules require it.

Do not invent uniqueness rules; derive them from approved business rules.

## 8. Relationships
Explicitly classify each relationship:
- one-to-one
- one-to-many
- many-to-many through a junction table

Every foreign key must document:
- Parent table/column.
- Child table/column.
- Cardinality.
- Required/optional.
- ON DELETE behavior.
- ON UPDATE behavior.
- Business meaning.

Prefer restrictive delete behavior for master/academic/history data. Cascade deletes require a clear reason and review.

## 9. Historical Data
ERP systems require history. Avoid destructive overwrite when the historical state matters.

Choose deliberately among:
- Current-state update.
- Effective-dated history.
- Status transition.
- Soft delete (`deleted_at`) where recovery/history is needed.
- Immutable transaction/history rows.

Academic results, payments, attendance and audit-sensitive records should not be casually hard-deleted.

## 10. Audit Fields
For new mutable business tables, normally consider:
- `created_at`
- `updated_at`
- `created_by` when actor audit is required
- `updated_by` when actor audit is required
- `deleted_at` only when soft delete is intentionally used

Do not add audit columns mechanically to pure reference/junction data if they have no value; document the decision.

## 11. Normalization and Denormalization
Default to a normalized transactional model (roughly 3NF) to avoid duplicate facts and update anomalies.

Denormalize only when:
- There is a measured/reporting need.
- The canonical source remains clear.
- Synchronization rules are documented.
- Integrity can be maintained.

Do not duplicate names/descriptions into transaction tables merely to avoid a join unless a historical snapshot is specifically required.

## 12. Transactions and Concurrency
Use database transactions for multi-table operations such as enrollment + dependent assignments, fee posting + ledger entries, or any operation that must be atomic.

For counters, balances, seat allocation or other concurrency-sensitive state, explicitly design for race conditions. Do not implement read-modify-write without considering locking/atomic updates.

## 13. High-Volume Tables
For attendance, logs, messages, transactions and other growing tables:
- Estimate growth.
- Keep rows narrow.
- Index common access paths.
- Avoid unbounded text where unnecessary.
- Plan archival/retention when required.
- Consider partitioning only after evidence shows it is useful; do not use it prematurely.

## 14. Schema Review Checklist
Before accepting a schema change, the AI must answer:
1. What business entity/fact is being stored?
2. Does an equivalent table already exist?
3. What is the tenant ownership path?
4. What are the PK, FKs and unique business keys?
5. Which columns are nullable and why?
6. Which queries will read this table most often?
7. Which indexes support those queries?
8. What reports will join this table?
9. What happens on parent deletion/update?
10. Will historical data be preserved?
11. Is the migration safe for existing rows?
12. How will rollback/recovery work?
