# MySQL Indexing Strategy

Indexes must be designed from real query patterns. The goal is fast, reliable reads without unnecessary write/storage overhead.

## Mandatory Index Review
Whenever the AI creates or changes a table, repository query, filter screen, dashboard or report, it must review indexing.

For each important query, record:
- WHERE columns.
- JOIN columns.
- ORDER BY columns.
- GROUP BY columns.
- Expected selectivity/cardinality.
- Tenant boundary (`college_id`) when applicable.
- Expected row count.

## Core Rules
1. Primary keys are indexed automatically.
2. Foreign-key columns used in joins should normally be indexed.
3. Add composite indexes for common multi-column filters rather than many isolated indexes when the query pattern supports it.
4. Composite index column order must follow expected query predicates and selectivity, respecting MySQL leftmost-prefix behavior.
5. Tenant-scoped high-volume queries often benefit from indexes beginning with `college_id` when nearly every request filters by college.
6. Add sorting columns to a composite index when it materially avoids expensive sorting for a frequent query.
7. Do not index every column.
8. Avoid duplicate indexes and redundant prefixes.
9. Low-cardinality columns such as a simple boolean/status alone are usually weak standalone indexes; combine them with selective columns when appropriate.
10. Long text columns must not be blindly indexed.

## Examples of Access-Pattern Thinking
Do not copy these mechanically; adapt to the actual schema.

Query pattern:
`WHERE college_id=? AND session_id=? AND student_id=?`
Candidate index:
`(college_id, session_id, student_id)`

Query pattern:
`WHERE college_id=? AND course_id=? AND attendance_date BETWEEN ? AND ?`
Candidate index:
`(college_id, course_id, attendance_date)`

Query pattern:
`WHERE student_id=? ORDER BY created_at DESC`
Candidate index:
`(student_id, created_at)`

## Report Indexing
For large reporting joins:
- Start from the most selective/tenant-limited set possible.
- Ensure join keys are indexed on both practical sides.
- Avoid functions on indexed filter columns when they prevent index use; use date ranges instead of wrapping indexed dates in functions where possible.
- Avoid leading-wildcard searches (`LIKE '%term%'`) on large transactional tables unless a dedicated search strategy is approved.

## Verification
For non-trivial/high-volume queries, use MySQL `EXPLAIN`/`EXPLAIN ANALYZE` in development when available.
The AI should inspect:
- Chosen key/index.
- Rows examined estimate.
- Access type.
- Temporary table/filesort indicators.
- Join order.

An index should not be added merely because `EXPLAIN` is imperfect; compare the workload and write cost.

## Index Documentation
Every custom index must be documented in the table spec with:
- Index name.
- Columns and order.
- Unique/non-unique.
- Query/use case it supports.

When a query/report is changed substantially, review whether its supporting indexes are still appropriate.
