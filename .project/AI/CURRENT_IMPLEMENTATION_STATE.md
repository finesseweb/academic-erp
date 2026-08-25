# Current Implementation State

## Common Academic Approval integration

**QA COMPLETE — 2026-08-25:** Curriculum and Academic Policy common approval workflow has been tested successfully, including role-based Inbox routing, multi-stage Approve, Return, Reject, correction/resubmission and final activation. Backend workflow-type matching is enforced.

### Frozen future rule
Academic Approval is a reusable engine. Future approval-enabled modules must integrate with the same workflow/stage/inbox mechanics while keeping subject-specific validation and final lifecycle behavior in their own handler/service.

## Academic Policy scope enhancement

**IMPLEMENTED / QA PASSED:** Academic Policy supports `UNIVERSITY -> DEGREE_LEVEL -> PROGRAM_TEMPLATE -> CURRICULUM`. Degree Level scope uses the existing active same-University Degree Level Master and `academic_policies.degree_level_id`.

## Academic Calendar

**QA COMPLETE / OWNER ACCEPTED — 2026-08-25:** University Academic Calendar foundation is implemented and tested. It introduces one official calendar per Academic Session plus normalized University calendar events, event-level College override governance, granular permissions, audit logging, Laravel/Inertia UI and date-bound validation.

## College Program Offerings

**IMPLEMENTED_IN_REPLACEMENT_PACKAGE — PENDING_REVIEW:** First College Academic Setup milestone. College selects same-University Program Template, approved active Curriculum and Academic Session; new offering starts INACTIVE and is explicitly activated. College permissions and audit are scope-aware.

### Current gate
Run College Program Offering QA defined in `NEXT_WORKFLOW.md`. Do not begin Intake / Seat Capacity until owner review passes.

## Test Data Cleanup maintenance update — 2026-08-25
- Cleanup Center expanded to cover Academic Calendar, College Program Offerings, Approval Workflows, Degrees and Degree Levels.
- Existing parent cleanup checks now understand Calendar / Program Offering dependencies.
- Full Academic Test Reset available only through the guarded Test Data Cleanup tool.
- Full reset preserves system/access core and uses explicit dependency order; foreign keys remain enabled.


## Program Offering Current-Selection correction — 2026-08-25
- New College Program Offering preselects the University's `ACTIVE + is_current` Academic Session.
- Other eligible PLANNED/ACTIVE sessions remain manually selectable.
- Curriculum selector now exposes only the derived Current `ACTIVE + APPROVED` Curriculum for the selected Program + Session.
- Backend enforces the same Current Curriculum rule; posting a Previous approved Curriculum is rejected.
- Curriculum currentness remains derived from ADR 007 amendment chains; no duplicate `is_current_version` column/flag is introduced.
- Existing Program Offerings are historical references and are never auto-relinked when Session Current status or Curriculum Current version changes.

## Global UI/data-selection consistency correction — 2026-08-25
- Current ACTIVE Academic Session default is now standardized for new session-dependent forms.
- Corrected existing Add Curriculum, Add Academic Policy, Add Academic Calendar, and College Program Offering behavior.
- Existing records are never automatically reassigned when Current Session changes.
- New project-wide `UI_DATA_SELECTION_CONSISTENCY.md` also freezes display-order, dependent-dropdown, current/historical-reference, status filtering and backend-parity rules for future modules.

## Logout redirect correction — 2026-08-25
- Fortify logout response is explicitly bound to redirect to the named `login` route.
- Logout no longer depends on the application's `/` home route, so Laravel/Inertia welcome page is not shown after logout.
- Rule applies consistently to all ERP roles/scopes.
- Added permanent `AUTH_NAVIGATION_CONTRACT.md` for future authentication/session-flow work.
