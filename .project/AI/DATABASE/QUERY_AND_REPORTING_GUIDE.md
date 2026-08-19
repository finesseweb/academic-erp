# Query and Reporting Guide

## Goal
Make data access predictable so AI agents can correctly join tables, perform CRUD and build reports without guessing.

## Before Writing a Query
The AI must identify:
1. The requested business metric/entity.
2. The result grain: one row per what?
3. Tenant/Affiliated College scope.
4. Academic/session/batch context where applicable.
5. Source-of-truth table(s).
6. Verified join path from `RELATIONSHIP_MAP.md`.
7. Status/effective-date filters.
8. Risk of duplicate rows from one-to-many joins.
9. Required indexes/access path.

## CRUD Rules
- Repository layer owns Laravel migrations / Eloquent/database access.
- Service layer owns business rules and transactions.
- Controller must not contain raw database logic.
- Use explicit selected fields/projections when possible.
- Validate parent ownership before creating/updating child rows.
- Every update/delete must be scoped by both record identity and authorization/tenant rules as appropriate.
- Use optimistic/concurrency controls where lost updates are a realistic risk.

## Join Rules
- Use only relationships documented in Laravel migrations / Eloquent/schema docs or verified legacy schema/code.
- Always understand cardinality before joining.
- One-to-many joins can multiply rows; aggregate at the correct grain.
- Do not use `DISTINCT` merely to hide a bad join.
- For optional relations, choose INNER vs LEFT JOIN deliberately.
- Apply tenant filters as early as practical.

## Reporting Rules
Every report PAGE_SPEC must document:
- Report purpose.
- Grain.
- Dimensions/groupings.
- Measures/formulas.
- Source tables.
- Join path.
- Required filters.
- Date/time semantics.
- Null/zero handling.
- Status exclusions/inclusions.
- Authorization/tenant scope.
- Sorting/pagination/export behavior.
- Performance expectations.

## Aggregation Accuracy
Before finalizing a report, test for:
- Double counting.
- Missing rows caused by INNER JOIN.
- Duplicate master mappings.
- Records outside the selected academic session.
- Cross-college leakage.
- Null values that alter SUM/AVG calculations.
- Denominator definition for percentages.

## Performance
For large reports:
- Filter tenant/session/date early.
- Select only required columns.
- Prefer set-based aggregation over N+1 queries.
- Avoid running one query per displayed row.
- Use pagination for detailed lists.
- Review indexes and `EXPLAIN` for expensive queries.
- Consider precomputed summaries only after correctness and measured performance justify them.

## Query Documentation
When an important query/report is introduced, update the relevant table specs with the access pattern and index support. For complicated reports, add the verified join path to `RELATIONSHIP_MAP.md`.
