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

### Stage 1 QA prerequisite correction (2026-08-27)
Before testing College Admission Form Setup, QA must begin at University level:
1. University login → Admission Form Setup.
2. Create/activate an appropriate University base template when governance requires one.
3. Enable the target College and choose its governance mode.
4. Explicitly allow College fee override only when intended.
5. Sign in as the College user and verify the menu is hidden when disabled and visible only when enabled + RBAC-authorized.
6. Continue Stage 1 Form Builder/Internal Application Entry QA.

After Stage 1 QA acceptance, resume the documented checkpoint: Interview QA → Merit / Roster Generation.

## Applicant → Student identity gate (added 2026-08-31)
1. QA Applicant Registration/Login per College mapping.
2. QA optional email verification and CAPTCHA gates.
3. QA mapped form after authenticated login, including conditions/files/fees.
4. QA submitted application appears in Application Candidates with applicant ownership.
5. When Admission Approval/Enrollment is implemented, create Student master first and call `ApplicantStudentPromotionService::enableStudentAccess()` in the same transaction.
6. Never create a second login for an admitted applicant.

### Stage 1 QA correction — RBAC runtime regression (2026-08-31)
Before continuing Applicant Registration QA, verify that both University and College Admission Form Setup pages load without querying `college_admission_form_access_controls`. Access must resolve only from normal role/permission/scope assignments. College extension creation must still require the selected University Base to have `Allow College Override = Yes`; College mapping of an ACTIVE University Base remains independent of that structural override flag.

### Stage 1 QA addendum — College Admission Form Setup render recovery
Before continuing Applicant Registration QA, confirm College Admission Form Setup renders normally and the Curriculum applicability dropdown contains only the current approved ACTIVE Curriculum version after amendments. This is a Stage 1 QA correction and does not advance the frozen hierarchy.

### Stage 1 QA runtime note — 2026-08-31 (ADR 037)
Before continuing Applicant Registration QA, verify `/college/{college}/admission-form-setup` SSR-renders successfully even when existing templates contain unloaded/empty nested relations. Missing render-only collections must degrade to empty sections, never a white screen.

- Stage 1 QA: test Applicant Portal theme rendering in available themes; applicant DOB + dynamic DATE fields must use shared DatePicker; test Forgot Password; test Email Verification Required ON/OFF including SMTP delivery, signed verification link, resend throttling and application redirect.

- QA: Applicant portal should submit without seat/category when mapping `seat_selection_required = false` (default), and require a valid active seat bucket only when explicitly enabled. Verify real email verification after SMTP configuration.

### Stage 1 QA guard — Applicant auth isolation
- Verify College Admin and University/internal users can log in without being affected by Applicant Registration email-verification settings.
- Verify Applicant with Email Verification Required = ON is blocked only inside the mapped Applicant Admission Portal until verified.
- Verify Applicant with Email Verification Required = OFF proceeds directly to the application.

### Applicant portal QA checkpoint
- Verify `/apply/{slug}` contains no internal Dashboard/sidebar/header.
- Verify theme changes flow through public applicant portal tokens.
- Verify mapped College, Program and Admission Cycle context is correct.
- Verify registration ON/OFF, login, forgot-password link, email verification and shared DOB DatePicker.

### Applicant public gateway QA after ADR 042
- Verify `/apply/{slug}` has no internal Dashboard/sidebar/header.
- Verify College/University, Program, Admission Cycle and Session context display from mapping.
- Verify New Applicant / Existing Applicant tabs and Registration Disabled login-only behavior.
- Verify DOB uses shared ERP DatePicker and theme changes propagate through token-based styling.

### Stage 1 QA — Public Academic Preference / Preview
1. Run migration `2026_08_31_140000_create_admission_application_academic_preferences.php`.
2. Open an ACTIVE mapped public form as an Applicant.
3. Confirm Logout returns to the mapped public gateway and does not affect internal ERP auth rules.
4. Confirm only Disciplines belonging to the mapped Program Template are selectable.
5. Confirm Specialization is required only when the selected Discipline has configured Specializations.
6. Confirm mandatory curriculum papers are shown as auto-allotted and CHOICE slots enforce their configured min/max.
7. Confirm application submission works irrespective of seat capacity/reservation bucket availability.
8. Confirm final Review & Submit displays academic selection, applicant profile, entered template values and fee context.
9. Confirm persisted academic preference/course choices match the exact mapped Program Offering Curriculum.
10. After Stage 1 QA, return to Interview QA, then Merit/Roster as already frozen; do not pull seat allocation into the public form.
