# ADR 138 — Student Benefit Bulk Candidate Resolution and Auditable Removal

**Date:** 2026-09-08  
**Status:** Accepted / Implemented — QA Pending

## Context
Bulk Student Benefit candidate loading was originally joining `academic_disciplines` through `program_templates.discipline_id`. That column was removed when Program Template disciplines became many-to-many, while each admitted student's selected discipline is persisted in Admission Academic Preference. The failed bulk request was hidden by the client and looked like an empty eligible list.

Operationally, a scholarship/concession/waiver may also be assigned by mistake. A hard delete is inappropriate because Student Benefits affect Fee Demand financial state and must remain auditable.

## Decision
1. Bulk candidate academic grouping uses Degree from Program Template and Discipline from the student's `college_admission_application_academic_preferences.discipline_id`.
2. Missing academic preference discipline is represented as General / No Discipline and must not exclude the Fee Demand from bulk consideration.
3. Client-side bulk candidate request failures are shown explicitly; they must not be presented as a valid zero-eligible result.
4. Active Student Benefits support an auditable Remove action under existing cancel permission.
5. PENDING removal changes only benefit status/audit fields; no Fee Demand adjustment exists to reverse.
6. APPROVED removal runs transactionally, reverses exactly the stored sanctioned amount from Fee Demand `adjusted_amount`, recalculates `outstanding_amount` and demand status, and marks the benefit CANCELLED with reason/user/time.
7. Gross Fee Demand `total_amount` remains immutable.
8. Removed benefits are retained; no hard delete is performed.
9. CANCELLED benefits do not block future same-scheme assignment and do not consume eligible fee-item capacity.

## Consequences
- Bulk hierarchy follows the student's actual admitted academic discipline instead of obsolete Program Template structure.
- Query/runtime failures are distinguishable from genuine no-eligible-student cases.
- Mistaken benefits can be corrected without losing audit history or corrupting Fee Demand financials.
