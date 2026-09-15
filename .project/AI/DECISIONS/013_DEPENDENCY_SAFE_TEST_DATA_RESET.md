# ADR 013 — Dependency-Safe Test Data Cleanup and Full Academic Reset

## Status
ACCEPTED FOR IMPLEMENTATION / PENDING OWNER REVIEW

## Decision
The Test Data Cleanup Center is the only supported destructive test-data cleanup mechanism for the ERP.

It must remain dependency-aware:
- Never globally disable foreign keys.
- Never use blanket `TRUNCATE` for ERP domain tables.
- Delete child/leaf records first, then their parents.
- Record-by-record cleanup must block a parent when downstream references still exist.
- Every newly implemented module must register its cleanup dependency rules when it introduces persistent test data.

## Full Academic Test Reset
A guarded full reset is allowed for development/testing environments only.

The full reset deletes currently implemented academic/test domain data in explicit child-first order, including:
- approval request stages / requests
- College Program Offerings
- Academic Calendar Events / Academic Calendars
- Academic Policy configuration / version chains
- Curriculum mappings / slots / terms / curricula
- Program Template discipline/specialization mappings
- Approval Workflow stages / workflows
- Courses
- Program Templates
- Disciplines / Specializations
- Course Categories / Course Types
- Degrees / Degree Levels
- Academic Sessions

## System Core Preserved
Full Academic Test Reset MUST preserve:
- University Profile
- Affiliated Colleges
- Users/login accounts
- protected/system Roles
- Permissions and Role-Permission grants
- College role/scope assignments
- Authorized Signatories
- Audit Logs
- Laravel migrations and framework/system tables

This prevents lockout and keeps the security/audit foundation intact.

## Future Rule
When Intake, Reservation, Batches, Sections, Admissions, Students, Fees, Faculty, Course Delivery, Attendance, Assessment, Examination, Results, Certificates, or other modules are implemented, their cleanup dependencies must be added before they are considered QA-complete.
