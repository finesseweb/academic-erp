# College Program Offerings Page

## Purpose
Allow an authorized College user to operationally adopt University-defined academic structure without creating duplicate academic masters.

## Route
`GET /college/{college}/program-offerings`

## Required Inputs
1. Academic Session — same University; only `PLANNED` or `ACTIVE`. When creating a NEW offering, the University's `is_current = true` + `ACTIVE` Academic Session is preselected automatically. Other eligible sessions remain selectable.
2. University Program — active same-University Program Template.
3. Curriculum — must be the CURRENT `APPROVED` + `ACTIVE` version for the same University, selected Program and selected Academic Session.

## Lifecycle
- Create -> `INACTIVE`
- Explicit Activate -> `ACTIVE`
- Deactivate -> `INACTIVE` without deletion
- Edit preserves the same offering record and re-validates every academic reference.

## Permissions
- `college_program_offering.view`
- `college_program_offering.create`
- `college_program_offering.update`
- `college_program_offering.enable`
- `college_program_offering.disable`

All are College-delegable and checked through the existing `hasCollegePermission(permission, college_id)` authorization path.

## UI
- College identity is visible in the header.
- Add/Edit uses the existing Dialog + Inertia Form pattern.
- New Offering dialog preselects the University's current ACTIVE Academic Session.
- Editing an existing Offering preserves that Offering's stored Academic Session; changing the University's current session never rewrites historical Offerings.
- Academic Session dropdown still lists other eligible `PLANNED`/`ACTIVE` sessions for authorized setup work.
- Curriculum choices are filtered client-side by selected Program + Academic Session.
- The server supplies only CURRENT `APPROVED` + `ACTIVE` Curriculum versions and backend validation repeats the current-version rule authoritatively.
- Curriculum currentness follows the existing Curriculum amendment chain and is NOT a separate mutable flag.
- Table columns: Program, Academic Session, Curriculum, Status, Actions.
- If the College is inactive, the page remains viewable but is read-only.
- Empty state explains that Program Offerings must be created before later College Academic Setup.

## Business Rules
- University academic masters remain authoritative.
- No College-local Program Template or Curriculum copy is created.
- One Program Template may appear only once per College + Academic Session.
- For NEW/UPDATED Program Offering selection, a Curriculum is current only when it is `ACTIVE + APPROVED` and has no direct `APPROVED` amendment child.
- If V1.0 is historically referenced by an existing Offering and V1.1 later becomes approved/current, the existing Offering remains linked to V1.0. No automatic historical relinking is allowed.
- Changing `AcademicSession.is_current` affects defaults for new setup only; it never migrates existing Program Offerings.
- Program Offering activation is required before it can be consumed by later Intake, Batch, Section, Fee, Admission or Course Delivery modules.
- Intake / Seat Capacity is NOT stored here.

## QA Gate
1. Valid same-University Program + Session + approved Curriculum creates an INACTIVE offering.
2. Curriculum mismatching Program or Session is rejected by backend.
3. Duplicate College + Program + Session is rejected.
4. Cross-University Program/Curriculum/Session IDs are rejected.
5. Activation succeeds only while all references are still valid.
6. Inactive College cannot mutate offerings.
7. User without College-scoped permission receives 403 and sidebar item is hidden.
8. Activate/Deactivate writes explicit College-scoped audit rows.
9. Production frontend build passes and page is responsive.
10. New Offering auto-selects the University's current ACTIVE Academic Session when one exists.
11. Curriculum dropdown excludes Previous approved versions and shows only the derived Current approved ACTIVE version for the selected Program + Session.
12. Backend rejects a Previous/superseded Curriculum ID even if manually posted.
13. Changing the University Current Academic Session does not modify an existing Offering.
14. Owner review completes before Intake / Seat Capacity starts.

## Protected College Administrator Permission Contract
The protected system role `COLLEGE_ADMIN` must always receive all five Program Offering permissions through migrations:
- `college_program_offering.view`
- `college_program_offering.create`
- `college_program_offering.update`
- `college_program_offering.enable`
- `college_program_offering.disable`

These permissions remain `is_college_delegable = true` so an authorized College Administrator may delegate an appropriate subset to College-owned custom roles. However, the protected `COLLEGE_ADMIN` system role itself is migration-synchronized and read-only in the Role Permission Matrix; its mandatory Program Offering permissions must therefore appear **selected + disabled**, never unselected + disabled.
