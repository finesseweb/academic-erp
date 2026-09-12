# Intake Migration FK Naming Correction — 2026-08-25

The initial Intake / Seat Capacity migration used Laravel-generated foreign-key names.
MySQL rejected the generated `college_program_intake_allocations_college_program_intake_id_foreign`
identifier because it exceeded MySQL's 64-character identifier limit.

Correction:
- Intake/Allocation foreign keys use explicit compact constraint names.
- Schema relationships and delete behavior are unchanged.
- No business logic or hierarchy change.
