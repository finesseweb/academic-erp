# Laravel migrations / Eloquent / MySQL Migration Workflow

## Purpose
All schema changes must be versioned, reviewable, reproducible and safe.

## Step 1 - Discover
Before changing schema:
1. Read the relevant PAGE_SPEC.
2. Read `SCHEMA_CATALOG.md`, `RELATIONSHIP_MAP.md` and relevant `TABLE_SPECS`.
3. Inspect `prisma/schema.prisma`.
4. For migrated modules, inspect the legacy MySQL schema and relevant Zend models/queries.
5. Determine whether the requirement can use existing schema unchanged.

## Step 2 - Design
If a change is needed, document:
- Tables/columns affected.
- Business reason.
- PK/FK/unique rules.
- Tenant ownership.
- Null/default strategy.
- Indexes and the queries they support.
- Data migration/backfill requirement.
- Compatibility risk.
- Rollback/recovery approach.

## Step 3 - Update Laravel migrations / Eloquent
Update `prisma/schema.prisma` so Laravel migrations / Eloquent accurately maps the physical MySQL schema.
Use mapping (`@map`/`@@map`) when preserving legacy physical names is beneficial.

## Step 4 - Generate Migration
Generate a named development migration, for example:
`npx prisma migrate dev --name add_student_enrollment_indexes`

Project commands:
- `npm run prisma:validate` validates the Laravel migrations / Eloquent schema.
- `npm run prisma:migrate:dev -- --name <migration_name>` creates/applies a development migration.
- `npm run prisma:migrate:status` inspects migration state.
- `npm run prisma:migrate:deploy` applies committed migrations during a controlled deployment.
- `npm run db:verify` performs a read-only MySQL connection/version probe.

Do not run migration commands against production automatically.

## Step 5 - Review SQL
Before applying/accepting the migration, inspect SQL for:
- DROP/TRUNCATE operations.
- Unintended table recreation.
- Column type narrowing.
- NOT NULL additions on populated tables without safe backfill/default.
- Foreign keys that existing data violates.
- Expensive indexes on large tables.
- Rename interpreted as drop + add.
- Data-loss warnings.

If unsafe, stop and redesign the migration.

## Step 6 - Backfill/Data Migration
When existing records require transformation:
- Separate schema change and backfill into safe steps when useful.
- Make scripts idempotent where possible.
- Validate row counts and invalid/orphan records.
- Do not silently discard data.

## Step 7 - Apply to Development/Test
Apply to a non-production database first.
Run Laravel migrations / Eloquent generation and application tests.

## Step 8 - Validate
Validate:
- CRUD.
- FK/unique constraints.
- Tenant isolation.
- Existing feature compatibility.
- Important report queries.
- Index usage/performance for high-volume paths.

## Step 9 - Update Database Intelligence Docs
Every approved schema change must update:
- `SCHEMA_CATALOG.md`
- `RELATIONSHIP_MAP.md`
- Relevant `TABLE_SPECS/*.md`
- Relevant PAGE_SPEC Database/Data Access sections
- Change history/changelog as required

The documentation and actual Laravel migrations / Eloquent/MySQL schema must not knowingly drift.

## Production Principle
Production migration execution is a separate controlled deployment activity. AI may prepare and review migration files, but must not independently apply destructive or unapproved production schema changes.
Application startup must not automatically invoke `prisma migrate dev` or `prisma migrate deploy`.
