# ADR 162 — ADR 161 Partial Migration Recovery

## Decision
The ADR 161 migration is made re-runnable after a partial MySQL DDL application. `academic_calendar_term_periods` is created only when absent, and the `academic_calendar_events.academic_calendar_term_period_id` linkage is added only when absent.

## Reason
MySQL can retain DDL created before a migration failure while Laravel does not mark the migration as completed. A subsequent `php artisan migrate` then fails with error 1050 because the table already exists.

## Behaviour
- Preserve an already-created `academic_calendar_term_periods` table.
- Continue ADR 161 by adding the event linkage only if it is missing.
- Fresh databases still execute the complete ADR 161 schema normally.
- `down()` is guarded for the optional event column.
- No business-rule, UI, RBAC, fee, cleanup, or route behaviour changes.

## QA
1. Replace the ADR 161 migration with this patched version.
2. Run `php artisan migrate`.
3. Confirm migration `2026_09_09_161000_link_academic_calendar_to_curriculum_terms` completes.
4. Run `php artisan optimize:clear`.
5. Continue ADR 161 Academic Calendar QA.
