# Delivery and Legacy Reference Plan

This is a new ERP application with a brand-new database. It is not a database-by-database migration of the Zend Framework 1 schema.

Legacy Zend code/database may be used as a reference when reproducing an existing business feature. Extract the required business behavior, validations, terminology, reports and edge cases, then implement them using the new architecture and new schema.

## Recommended Delivery Phases
0. Project foundation: repositories/apps, environments, Laravel, React, Laravel migrations / Eloquent, MySQL, standards.
1. Core platform: University/Affiliated College model, users, authentication, sessions, audit foundation.
2. RBAC: roles, permissions, scoped assignments, guards and access-management UI.
3. Realtime foundation: Socket.IO gateway, socket authentication/authorization, room strategy and notification foundation.
4. Academic masters: departments, programs, academic sessions, batches, terms/semesters, courses and required relationships.
5. Business modules: students/admissions, allocation, attendance, examination/results, fees, certificates, library, reports and other approved modules according to dependency order.

For every phase, design only the domain needed now while keeping extension paths clean. Do not create speculative tables without a defined requirement.
