# ADR 004 — University with Affiliated Colleges and Layered Fee Governance

## Status
Approved.

## Decision
The ERP models one University as the organizational root with multiple affiliated Colleges.

Authorization scope can narrow from University to College, Faculty/School, Department, Program, Course/Class, and own/linked records.

Fee governance is layered:
- University-fixed;
- College-configurable;
- College-defined where permitted.

College-level Installment Plans are supported as payment schedules over approved payable fees and cannot alter University-fixed amounts.

## Consequences
- Existing generic Institution page specs are redefined as Affiliated College pages.
- Super Admin is University-level.
- Database ownership must be decided per domain rather than blindly adding College ownership.
- Finance history is non-destructive and auditable.
