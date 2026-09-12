# ADR 117 — Fee Structure Billing Context Cannot Be Reinterpreted With Existing Items

## Status
Accepted — 2026-09-05

## Context
A recurring Fee Structure can be configured as Semester/Term-wise or Academic-Year-wise. Fee Items store a reusable parent amount plus period-specific applicability/amount/policy children. If the parent Fee Structure's billing basis or academic scope is changed after Fee Items already exist, those existing items can be silently reinterpreted under a different set of Billing Periods. For example, an Academic Year 1 Library Fee of INR 2,000 could appear in every Semester after changing the basis to PER_TERM because the parent item amount becomes the fallback for newly derived periods.

That behavior is financially unsafe and does not match the period-first mental model: a Fee Item is applicable only in the Billing Period where the administrator explicitly configured it.

## Decision
Once a Fee Structure contains one or more Fee Items, its billing-period context is locked. The administrator must remove the configured Fee Items before changing any field that changes the derived billing coverage, including Fee Collection Basis, specific period, Academic Session, Program Offering / Program Template, or Curriculum.

The backend enforces this rule even if a request is submitted outside the UI. The Fee Structure edit UI also locks Fee Collection Basis while items exist and explains that Fee Items must be removed first.

No automatic conversion, copying, redistribution, or reinterpretation of Fee Item amounts is permitted when changing Semester/Term/Academic-Year/One-Time billing semantics.

## Consequences
- Existing configured amounts cannot silently spread into newly derived periods.
- Switching Academic Year-wise ↔ Semester-wise is explicit: remove items, change basis, then add charges only to intended periods.
- Fee Setup remains period-first and auditable.
- No migration is required.
