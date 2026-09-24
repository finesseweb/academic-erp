# 2026-09-17 — ENR-3.6 Student Management RBAC + Audit Alignment

Fixed the ENR-3 closure defect where Student Identity borrowed Enrollment View and appeared as an incomplete/orphan RBAC resource. Student Enrollment and Student Identity now share the `Student Management` permission module. Added separate Identity View, retained sensitive Identity Manage, aligned sidebar/controller enforcement, and added audit logging for Identity Rules changes. Existing Identity assignment auditing remains authoritative. No new domain table/column.
