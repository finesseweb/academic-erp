# Next Workflow — Admission Form Configuration & Internal Entry Stage 1 QA

This is the owner-approved prerequisite branch inserted before continuing from Interview QA to Merit / Roster. It must not reorder or replace the frozen downstream Admission hierarchy.

Validate Stage 1:
1. Run migrations and confirm Form Template/Step/Field/Option, Form Mapping, Application Fee Rule and Application Field Value tables exist, plus new snapshot/mode fields on `college_admission_applications`.
2. Confirm `college_admission_form.view/manage/map` and `college_application_fee.manage` permissions are present and College scope is enforced server-side.
3. Create a College-owned template, assign a manager, add multiple steps, and add representative fields: text, dropdown, radio, checkbox/multi-select, file/image and Yes/No.
4. Confirm all generated inputs use the existing ERP theme/layout and not raw mismatched page styling.
5. Map the template first broadly and then specifically (for example College default then Admission Cycle override); confirm the most specific active mapping resolves.
6. Configure a broad Application Fee and a more-specific override plus one FREE rule; confirm most-specific resolution.
7. Create a REGULAR internal application. Confirm active Admission Cycle -> exact Program Offering -> active Intake/seat bucket choices are used and an ACTIVE Selection Rule is still required/locked.
8. Confirm dynamic required fields/options/file extension/max-size validation is enforced server-side and responses persist on the same application.
9. Confirm the resolved Form Template and Application Fee rule/amount/currency are snapshotted on the application; later configuration edits must not silently rewrite historical submitted data.
10. Create a DIRECT internal application against a valid active Intake/seat bucket without requiring a Selection Rule; confirm no duplicate application/candidate transaction table/path is created.
11. Confirm Direct applications do not accidentally enter Score/Interview pages that require locked Selection Rules.
12. Re-test an existing REGULAR application through Eligibility -> Score Capture -> Interview and confirm Stage 1 introduced no regression.
13. Confirm University-owned template/fee actions require University permission and College users cannot escape their College scope.
14. Confirm audit/ownership expectations and file storage behavior are acceptable before public/self-service form work is considered.

After Stage 1 owner acceptance, resume the previous checkpoint exactly:
**Interview Scheduling / Evaluation QA -> Merit / Roster Generation**.

Do not implement Merit / Roster until both Stage 1 QA and the outstanding Interview QA are accepted.

### Stage 1 QA addendum — RBAC-native access
1. Confirm University/SUPER_ADMIN sees Admission Form Setup by default.
2. Confirm a College user without `college_admission_form.view` does not see the College Admission Form Setup menu and receives 403 on direct access.
3. Create or use a College-scoped custom role and assign `college_admission_form.view` through Access Management → Roles → Permissions. Assign that role to a test user for the target College.
4. Confirm the menu appears automatically for that user without any Admission-specific enable checkbox.
5. Add `college_admission_form.manage`, `college_admission_form.map`, and/or `college_application_fee.manage` and verify actions appear only as permissions are granted.
6. Remove a permission and verify menu/action/backend access is revoked automatically.
7. Continue Stage 1 Form Builder/Application Entry QA, then return to Interview QA and Merit/Roster.

## Additional Stage 1 QA — Conditional/Scoped Fields

Before Stage 1 is accepted:
1. Create `Sports Quota?` as Yes/No, then create required File field `Sports Certificate` with condition `Sports Quota? = YES`.
2. Verify certificate is hidden for No, appears for Yes, is required only for Yes, and backend rejects a Yes submission without the file.
3. Save Yes + certificate, edit the draft to No, save, then confirm the hidden certificate response is removed from active field values.
4. Create a field scoped to one Degree Level and verify it appears only for Admission Cycles whose Program Offering resolves to that Degree Level.
5. Create a Curriculum-scoped field and verify it appears only when the selected Program Offering is linked to that exact Curriculum version.
6. On College extension, create a conditional field whose source is an inherited University base field; verify live display and backend validation.
7. Confirm a mismatched Degree → Program → Offering → Curriculum/Cycle scope combination is rejected while configuring the field.

### Stage 1 QA addendum — University override + current Curriculum
Before Stage 1 acceptance, verify:
1. University template with College override OFF cannot be extended from College login.
2. Turning override ON exposes that ACTIVE University base for College extension creation.
3. University cannot turn override OFF while an ACTIVE College extension depends on it.
4. Curriculum applicability dropdown contains only current approved ACTIVE Curriculum versions; approved superseded parents do not appear after an amendment is approved.
5. forged/stale superseded Curriculum IDs are rejected by backend validation.
After these pass, continue the existing Stage 1 QA → Interview QA → Merit/Roster resume contract. Do not advance the frozen hierarchy early.

### Stage 1 RBAC QA addition (2026-08-27)
Before Stage 1 acceptance, verify the Permission catalog exposes the complete action-level Admission Form permission set and that a custom College role can be granted only selected actions through the normal Roles → Permissions matrix.

### Stage 1 QA addendum — CRUD + Panel grouping
Before resuming Interview QA, verify Template/Step/Panel/Field edit/delete, panel-less fields, panel-grouped fields, College Administrator default permissions, and University `Allow College Override` enforcement.

### Stage 1 mapping governance clarification (ADR 028)
During Stage 1 QA, University remains owner of form definition/governance. College owns Program Offering + Admission Cycle mapping. `Allow College Override` controls structural extension only and must not be used as a mapping/use gate. Complete this mapping QA before returning to Interview QA.

### Stage 1 QA addendum — Public candidate application URL (ADR 029)
Before Stage 1 acceptance, verify:
1. College maps an ACTIVE form to one existing Program Offering + Admission Cycle, then enables Public Application.
2. A stable `/apply/{slug}` link appears and opens without authentication.
3. Before `application_start_date`, the public page shows Upcoming and backend rejects submission.
4. From start through end date inclusive, the form accepts submission; after `application_end_date` it shows Closed and backend rejects submission.
5. Disable the public link and confirm the URL no longer resolves; re-enable and confirm the same slug is reused.
6. Confirm only active Regular seat buckets with a valid ACTIVE Selection Rule are offered publicly.
7. Submit one public candidate and confirm the same record appears in College Applications/Candidate Eligibility as `entry_source=PUBLIC`, with exact mapped Offering/Cycle, Form/Fee snapshots and Selection Rule lock.
8. Confirm a DIRECT-only template cannot be public-enabled and Direct Admission stays internal.
After this passes, continue the existing Stage 1 QA → Interview QA → Merit/Roster resume contract.

### Stage 1 QA correction — conditional source ordering
Admission Form conditional-source selection now follows runtime Step order. Source fields are grouped by Step; later-step fields are not eligible, and Laravel enforces the same rule. Continue Stage 1 QA before resuming Interview QA.

### Stage 1 QA patch — public step layout
Verify both mapping layouts: SAME_WINDOW must render the continuous application form; NEW_WINDOW must show one application screen at a time, preserve entered values/files while navigating Previous/Next, enforce current-screen required inputs before Next, and submit only from the final screen. Continue Stage 1 QA after this check.
