# Database Rules

## Database Technology
- MySQL is the primary relational database.
- Laravel migrations / Eloquent is the application ORM and migration authority.
- Development, test, staging and production databases must be separate.
- Never make uncontrolled direct production schema changes.

## Runtime Connectivity
- React and Laravel share one application server; MySQL is hosted on a separate database server.
- Production database traffic uses a private/restricted network route and a least-privilege application account.
- Development, test, staging and production connection strings remain outside source control.
- Use the backend `db:verify` command for a non-mutating connectivity check before migration or deployment work.
- Set connection limits and timeouts deliberately in the Laravel migrations / Eloquent MySQL URL for the capacity of both servers.

## Brand-New Database Rule
This project uses a new database designed from scratch for the new ERP.

For every new/modified page, API, realtime feature, background job, import/export, dashboard or report:
1. Read the feature/page specification.
2. Inspect `prisma/schema.prisma` and `.project/AI/DATABASE/`.
3. Identify the required business entities and relationships.
4. Reuse an existing NEW-ERP entity when it represents the same concept.
5. Create a new table/relation when the domain requires one; do not create page-specific duplicate entities.
6. Modify existing new-ERP schema professionally when requirements evolve.
7. Update Laravel migrations / Eloquent and generate a named/versioned migration.
8. Review generated SQL and migration safety.
9. Apply/test in development/test before controlled higher-environment deployment.
10. Update schema catalog, relationship map and table specs.

Legacy Zend/MySQL structures may be consulted for business understanding only. Do not copy legacy tables automatically and do not preserve poor legacy design merely for compatibility unless an explicit integration/migration requirement is approved.

## Schema Quality Requirements
Every design must consider primary keys, University/College ownership, foreign keys/referential actions, nullability, correct MySQL types, business uniqueness, indexes based on actual access patterns, timestamps, history/soft-delete strategy, validation, expected growth, reporting patterns, concurrency and transaction boundaries.

Prefer durable history over destructive deletion for financial, academic, authorization and audit-sensitive records.

## Safety and Change Policy
- Schema modification is allowed and expected when professionally required.
- Destructive changes (DROP/TRUNCATE/data-loss migrations/key-type changes) require explicit approval and a migration/data-preservation plan.
- Never modify production data automatically.
- Never invent relationships; define and document them deliberately.
- Never add indexes blindly; each index must support a known access pattern.
- Avoid redundant indexes and unnecessary `SELECT *`.
- Use transactions for atomic multi-table business operations.

## Mandatory Database Documents
Maintain `DATABASE_DESIGN_STANDARDS.md`, `INDEXING_STRATEGY.md`, `MIGRATION_WORKFLOW.md`, `SCHEMA_CATALOG.md`, `RELATIONSHIP_MAP.md`, `QUERY_AND_REPORTING_GUIDE.md`, and relevant `TABLE_SPECS/*.md`.

## Authorization Data Model
RBAC, scope, sessions and audit history are first-class new-ERP entities. Design them for this project; do not wait for or mirror legacy authorization tables. Authorization schema must support University/affiliated-College isolation and efficient permission resolution.

## University vs College Data Ownership
Before adding a foreign key or ownership column, classify the concept as University-owned, College-owned, or shared/inherited. Do not blindly add `college_id` to every model.

Financial models must preserve University-fixed controls, College-permitted configuration, installment schedule history/versioning, stable demand/receipt references, and non-destructive reversals/adjustments. See `DOMAIN/FEE_GOVERNANCE_AND_INSTALLMENTS.md`.

## Laravel + MySQL Implementation Standard

Database engine remains MySQL.

All schema changes must use Laravel migrations under:
`backend/database/migrations/`

Use Eloquent models and relationships.

Rules:
- foreign keys for real relationships;
- indexes for common filters/joins;
- unique constraints for business identifiers;
- transactions for critical multi-write workflows;
- avoid hard deletes for historical academic/financial records;
- do not store master names as repeated free text when a foreign key exists;
- migration filenames and table names must clearly reflect the module/domain.
