
## Immediate gate after ADR 169
Complete Academic Period QA before Fee Setup QA: verify effective-window years/dates, same-Curriculum occupied-date restriction, backend overlap rejection, edit behavior, and parallel dates across different Curricula. Then validate Fee Setup Standard Due Date against the resulting Academic Period. Do not resume Late Fine QA until this chain passes.

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

- QA Test Data Cleanup after applicant academic-choice changes: preview counts, clean one Admission Application, then verify Full Academic Reset removes academic preferences/course choices/dynamic field values/registration settings without FK errors.

- QA Admission academic packages: configure a CHOICE category with fixed History papers across multiple curriculum terms and verify Public + Internal application show only `History`; selecting it must persist all underlying mappings. Configure another source with more offered papers than the slot selection count and verify individual papers remain selectable. No semester headings should appear.

- QA Specialization visibility: template-only specialization with no mapped curriculum paper must stay hidden in both Public Application and College Admin Add Application; mapping the first active specialization-specific paper should make only that specialization appear.

- QA ADR 050: create a template specialization with no curriculum paper and confirm it remains available in Curriculum Mapping authoring but is absent from Applicant/Internal Application, Intake/Capacity, Reservation and Selection Rule screens. Add one active specialization-specific paper and confirm it becomes available downstream.

## Current resume point — 2026-09-03
Merit / Roster Generation -> **Seat Allocation / Consumption (implemented, Owner QA required)** -> Admission Confirmation / Approval -> Student Enrollment / Lifecycle.

Hierarchy completeness guard: Batches, Sections and College Academic Calendar remain pending College Academic Setup items. They must be implemented and linked before teaching-delivery modules depend on them; moving forward in Admission does not remove them from the roadmap.

## Current next workflow — 2026-09-03
Main-hierarchy gap closed: `Merit / Roster -> Document Verification -> Seat Allocation / Consumption` is now implemented and linked. After owner QA, the next Admission implementation is **Admission Confirmation / Approval**, consuming an ACTIVE Seat Allocation rather than re-deciding capacity.

Completeness checkpoint retained: College Academic Setup still requires **Batches -> Sections -> College Academic Calendar**. These are not considered complete merely because Admission processing is ahead; future Student Enrollment/teaching work must link to them logically.


## Current next workflow — 2026-09-04
Admission Confirmation / Approval is now IMPLEMENTED — OWNER QA REQUIRED. The Admission chain is complete through confirmation:
`Merit / Roster -> Document Verification -> Seat Allocation / Consumption -> Admission Confirmation / Approval`.

Before Student Enrollment / Lifecycle begins, complete the pending College Academic Setup block in this order:
**Batch Management -> Section Management -> College Academic Calendar**.

After these three are implemented and QA-accepted, implement **Student Enrollment / Lifecycle**, consuming only a `CONFIRMED` Admission. Student creation must create the Student master first and then call `ApplicantStudentPromotionService::enableStudentAccess()` in the same transaction; never create a second login.

## Current next workflow — 2026-09-04 Batch Management
Admission Confirmation / Approval owner QA is accepted.

**Batch Management is IMPLEMENTED — OWNER QA REQUIRED.**

QA Batch Management before moving forward:
1. Create Batch from the current ACTIVE Program Offering.
2. Verify it starts INACTIVE and inherits Program/Curriculum/Academic Session context.
3. Verify activation requires ACTIVE Intake / Seat Capacity.
4. Verify duplicate Batch code within the same Program Offering is blocked.
5. Verify Batch create/activation does not change Intake, Reservation, Seat Allocation or Admission counts.
6. Verify RBAC and exact College scope.
7. Verify Test Data Cleanup Batch target when no downstream records exist.

After Owner QA acceptance, implement exactly the next frozen milestone: **Section Management**. Then College Academic Calendar, then Student Enrollment / Lifecycle.


## Current next workflow — 2026-09-04 Section Management
Batch Management Owner QA is accepted.

**Section Management is IMPLEMENTED — OWNER QA ACCEPTED.**

QA Section Management before moving forward:
1. Create Section under the current ACTIVE Batch; verify it starts INACTIVE.
2. Verify each Batch is compact/collapsed by default, its Program/Curriculum/Session/Parent Program Intake and Section counts appear once in the header, and `Show Sections` expands only that Batch without changing Intake capacity.
3. Activate Section; verify ACTIVE parent Batch/Offering/Intake are required.
4. Verify duplicate Section code within the same Batch is blocked.
5. Verify ACTIVE Section disables/blocks parent Batch deactivation.
6. Deactivate Section and verify Batch deactivation becomes available again.
7. Verify exact College RBAC and Test Data Cleanup behavior.

After Owner QA acceptance, implement **College Academic Calendar**, then Student Enrollment / Lifecycle.


## Next workflow — 2026-09-04 College Academic Calendar
Batch Management and Section Management Owner QA are accepted.

**College Academic Calendar is IMPLEMENTED — OWNER QA REQUIRED.**

QA before Student Enrollment:
1. Adopt the ACTIVE University Academic Calendar for the College; verify it starts INACTIVE and the Academic Session/events are inherited.
2. Activate the College Calendar; verify University data is unchanged.
3. Verify an event with `allow_college_override = false` is read-only/locked at College level.
4. Override an event with `allow_college_override = true`; verify College effective title/date changes while University source values remain visible and unchanged.
5. Verify override dates outside the Academic Session are blocked.
6. Disable the override and verify the University event becomes effective again.
7. Verify RBAC, exact College scope, audit events and Test Data Cleanup.

After Owner QA acceptance, implement **Student Enrollment / Lifecycle**.

## Current next workflow — 2026-09-04 Fee Foundation
College Academic Calendar core override lifecycle and date-bound UI behavior have been QA-checked. Before Student Enrollment / Lifecycle, the project now inserts the Fee phase.

**Fee Foundation (Fee Heads + Fee Structures) is IMPLEMENTED — OWNER QA REQUIRED.**

QA in this order:
1. University: create a Fee Head, verify INACTIVE -> ACTIVE lifecycle.
2. University: create an ADMISSION Fee Structure for current Academic Session, optionally All Programs or one Program Template.
3. Add at least one active item; mark one item `Required for Enrollment Clearance`; activate the structure.
4. College: create/activate a College Fee Head, then create a Fee Structure under one exact Program Offering and verify Program/Session are inherited.
5. Add College fee items and activate; verify a second ACTIVE structure for the same exact scope + purpose is blocked.
6. Verify an ACTIVE structure cannot be edited until deactivated, and an ACTIVE Fee Head used by an ACTIVE structure cannot be deactivated.
7. University: verify College Applicability can be set to OPTIONAL or MANDATORY while the structure is INACTIVE.
8. College: verify a matching ACTIVE MANDATORY University structure is shown read-only and applies automatically; College can still create its own additional local structure.
9. College: verify a matching ACTIVE OPTIONAL University structure can be Adopted / Stop Using; ignoring it does not block College-owned structure creation.
10. Verify a University purpose/scope being absent does not block College structure creation, and University/College permissions remain separated.

After owner QA acceptance, implement **Admission Fee Demand Generation**, consuming only `CONFIRMED` Admissions and the applicable ACTIVE University + College `ADMISSION` Fee Structures. Then implement Payment/Adjustment -> Fee Clearance -> Student Enrollment / Lifecycle.

### Fee Foundation QA — Fee Head inheritance
Before Fee Demand work, verify ADR 098:
1. Create and activate a University Fee Head (for example Tuition Fee).
2. Open College Fee Management and confirm the University Fee Head appears as University-owned/read-only.
3. Create an INACTIVE College Fee Structure and add the inherited University Fee Head as a Fee Item without creating a duplicate College Fee Head.
4. Confirm the Fee Item saves and the College Fee Structure can activate when all existing activation guards are satisfied.
5. Confirm a direct College edit/status request against the inherited University Fee Head is rejected by existing ownership guards.
6. Confirm a College-local Fee Head can still be created and used for a genuine local charge.

### Fee Demand prerequisite — collection basis
Before Fee Demand implementation, use ADR 099 collection basis. Demand generation must use the Fee Structure basis plus Program Offering/Curriculum academic context; it must not infer semester/year recurrence from Fee Head or Purpose.

### Fee Demand prerequisite — period resolution contract
Before Fee Demand implementation, treat ADR 100 as binding: resolve College recurring periods from the exact Program Offering Curriculum, apply a period override when present, otherwise use the Fee Item default amount, then snapshot the resolved amount/period into the demand. Never generate missing College terms from Program Template duration.

### Fee Demand contract from ADR 101
When Fee Demand is implemented, resolve the exact College Curriculum period first. Skip any Fee Item explicitly excluded for that period; otherwise use its period override amount when present, falling back to the Fee Item default amount. Never create a zero-value demand merely to represent Not Applicable.

## Fee Structure period-first QA — ADR 102
Before Fee Demand implementation, QA both University and College Fee Management:
1. Create/edit a recurring Fee Structure and expand `Billing Periods`.
2. College `Every Semester/Term`: verify only ACTIVE Curriculum Terms of the exact Program Offering appear.
3. Add Tuition Fee under Semester 1 with one amount; add/reuse the same Fee Head under Semester 2 with a different amount and verify no duplicate Fee Head row is created.
4. Leave a Fee Head absent from one period and verify it does not appear there.
5. `Every Academic Year`: verify only complete Curriculum-derived year groups appear for College and each group shows the covered term names.
6. University: verify period cards derive from the selected Program Template and the same period-first Add Fee Item workflow is available.
7. Verify ACTIVE totals are calculated per billing period and ACTIVE Fee Structure remains read-only until deactivated.
8. Only after this QA is accepted proceed to Applicable Fee Demand / Fee Clearance.

### Fee QA continuation — configured totals and one-time Admission Fee
- Verify recurring structure header Configured Total equals the sum of ACTIVE/applicable Billing Period subtotals and changes when a period amount/applicability changes.
- Verify `ADMISSION + ONE_TIME` exposes one `One-Time Admission Charge` period and its Configured Total equals the ACTIVE Fee Items in that period.

## Resume checkpoint — 2026-09-04 end-of-day
Resume Fee Foundation Owner QA from this exact point:
1. Latest UI model is `Fee Structure -> Billing Periods -> Fee Items` for both University and College.
2. Existing College QA structure: `BA Academic Fee 2026`, purpose `ACADEMIC`, collection basis currently being tested as recurring; Curriculum currently exposes Semester 1 and Semester 2.
3. Verify the Fee Item Edit dialog has **no applicability checkbox**. A Fee Item shown inside a period is applicable by presence.
4. Verify Semester 1 Tuition can be INR 20,000 and Semester 2 Tuition can be INR 15,000 without changing Curriculum or creating duplicate Fee Heads.
5. Verify the structure header `Configured Total` equals the sum of ACTIVE/applicable period totals (example above = INR 35,000).
6. Then QA `ADMISSION + ONE_TIME`: it must expose one `One-Time Admission Charge` Billing Period; add Admission/Registration/etc. Fee Items only there and verify Configured Total.
7. After College QA, repeat the same period-first workflow at University level, including Program-Template-derived recurring period cards and University Fee Structure College Applicability (MANDATORY/OPTIONAL).
8. Do **not** start Fee Demand until this Fee Foundation QA is owner-accepted.

Next implementation after Fee Foundation acceptance: Applicable Fee Demand -> Payment/Adjustment -> Fee Clearance -> Student Enrollment/Lifecycle.

## Resume checkpoint — 2026-09-05 University Curriculum-bound Fee QA
1. Merge `academic-erp-fee-university-curriculum-period-consistency-fix.zip` and run `php artisan migrate`.
2. University → Fee Management → edit the existing `BA University Academic Fee 2026` (currently created as EVERY SEMESTER).
3. Select the exact ACTIVE + APPROVED BA Curriculum for the same Academic Session and save.
4. Expand Billing Periods. Expected: only ACTIVE terms actually defined in that Curriculum appear. If Curriculum currently has Semester 1 and Semester 2 only, University Fee must show only those two — never synthetic Semester 3–6 from Program Template duration.
5. Verify a recurring University structure cannot be saved/activated without Curriculum.
6. Then continue University period-first Fee Item QA and University OPTIONAL/MANDATORY College applicability QA.


### Resume checkpoint — University Fee current-Curriculum QA (2026-09-05)
Continue University Fee Foundation QA from the Curriculum selector.
1. Open/edit the University BA recurring Fee Structure.
2. Verify the Curriculum dropdown shows only the current ACTIVE + APPROVED BA Curriculum version for the selected Program Template + Academic Session.
3. Verify an older approved/superseded BA Curriculum version is absent.
4. Save the current Curriculum and confirm Billing Periods come only from its ACTIVE Curriculum Terms.
5. If an older Curriculum ID is manually submitted, backend must reject it as superseded/non-current.
6. Then continue University period-first Fee Item amounts, Configured Total, activation, and University→College optional/mandatory applicability QA.

## Resume checkpoint — 2026-09-05 period-specific Fee Item policy QA
Fee Foundation remains the active milestone. College recurring and College one-time Admission Fee QA had passed before this correction. University recurring Fee Structure was being prepared after fixing University Curriculum consistency/current Curriculum selection.

Next QA must first verify ADR 107 on an INACTIVE recurring structure:
1. Open Semester 1 Tuition and set e.g. Mandatory=Yes, Enrollment Clearance Required=Yes, Installment Allowed=No.
2. Open Semester 2 for the same Tuition Fee and set Mandatory=Yes, Enrollment Clearance Required=No, Installment Allowed=Yes.
3. Re-open both periods and verify the policies persist independently and each period row displays its own flags.
4. Verify configured total is unchanged by policy-only edits and period ACTIVE/INACTIVE status affects active subtotal/configured total.
5. Then resume University OPTIONAL structure activation/adoption QA using the current ACTIVE+APPROVED Curriculum only.

Do not start Fee Demand until this period-specific policy QA and University→College OPTIONAL/MANDATORY applicability QA are complete.

## Resume checkpoint — 2026-09-05 University → College Fee QA
1. Matching ACTIVE University OPTIONAL `BA University Academic Fee 2026` is visible at College and adoption visibility QA passed.
2. Verify `View Structure` is present before/after adoption and opens a read-only view showing Semester 1 Tuition INR 20,000, Semester 2 Tuition INR 15,000, configured total INR 35,000 and period-specific policies.
3. Adopt the OPTIONAL structure. Confirm `Stop Using` remains available and no University edit/add/delete controls appear.
4. With the University structure adopted, create/edit a College-local structure for the same BA Program Offering and attempt to add `Tuition Fee` over Semester 1. Expected: blocked as duplicate effective University Fee Head/billing coverage.
5. Add a genuinely local/different Fee Head (for example Lab/Activity Fee) to the College structure. Expected: allowed.
6. Stop Using the OPTIONAL University structure and repeat the local Tuition configuration. Expected: OPTIONAL policy no longer blocks because it is not effective.
7. After OPTIONAL QA passes, test a MANDATORY University structure: it applies automatically, has View Structure only (no Stop Using), and its overlapping Fee Heads are always protected from College duplication.

### Resume QA — ADR 110 effectiveness overlap integrity
Use the current BA test case.
1. Keep `BA University Academic Fee 2026` OPTIONAL + adopted/effective and keep `BA College Additional Fee 2026` INACTIVE with Semester 1 Tuition Fee + Library Fee configured.
2. Click **Activate** on the College structure. Expected: BLOCKED because Tuition Fee overlaps the effective University structure in Semester 1.
3. Click **Stop Using** on the OPTIONAL University structure, then activate the College structure. Expected: College structure activates successfully.
4. While the College structure is ACTIVE, click **Adopt University Structure**. Expected: BLOCKED because Tuition Fee is already effective in the College structure.
5. Deactivate/remove the conflicting College Tuition charge, then adopt again. Expected: adoption succeeds.
6. Later run the equivalent MANDATORY test: a matching MANDATORY University structure must not activate if it would duplicate an ACTIVE College charge.
Only after these tests pass should University→College Fee Foundation applicability be marked owner-QA complete and work move to Applicable Fee Demand.

### Resume QA — ADR 111 Fee Item removal
1. Keep/create an INACTIVE recurring College Fee Structure with the same Fee Head configured in Semester 1 and Semester 2.
2. Remove it from Semester 1. Expected: Semester 1 row disappears, Semester 2 remains, totals update.
3. Remove it from Semester 2. Expected: the final shared Fee Item disappears completely.
4. Add a ONE_TIME Fee Item and remove it. Expected: the item disappears completely.
5. Activate a structure. Expected: edit/remove actions are hidden and backend rejects direct removal until deactivated.
6. Repeat one recurring removal in University scope. College read-only University Structure view must show no delete action.

## 2026-09-05 — Applicable Fee Demand QA checkpoint
Applicable Fee Demand is IMPLEMENTED — OWNER QA REQUIRED.
QA in order: confirmed admission appears -> generate Period 1 -> verify adopted/mandatory University + active College items -> verify exact amounts and Mandatory/Clearance/Installment badges -> verify duplicate period demand blocked -> verify cancelled unpaid demand retains history and can be regenerated -> verify Period 2 excludes ONE_TIME and uses Period 2 settings.
After QA acceptance, implement Payment / Scholarship-Waiver-Adjustment / Installment execution, then Fee Clearance, then Student Enrollment. Do not jump directly from Demand to Enrollment.

## 2026-09-05 — Applicable Fee Demand QA checkpoint
Applicable Fee Demand is IMPLEMENTED — OWNER QA REQUIRED.
QA in order: confirmed admission appears -> generate Period 1 -> verify adopted/mandatory University + active College items -> verify exact amounts and Mandatory/Clearance/Installment badges -> verify duplicate period demand blocked -> verify cancelled unpaid demand retains history and can be regenerated -> verify Period 2 excludes ONE_TIME and uses Period 2 settings.
After QA acceptance, implement Payment / Scholarship-Waiver-Adjustment / Installment execution, then Fee Clearance, then Student Enrollment. Do not jump directly from Demand to Enrollment.

## Resume checkpoint — Fee Demand QA after ADR 115
1. Run migration `2026_09_05_170000_add_generation_mode_to_fee_demands.php`.
2. Open College → Fee Management → Fee Demands and confirm the page shows Admission Stage = Automatic and Program Offering → resolved Academic Policy for later-period bulk planning.
3. Confirm a new eligible Admission. Expected: Admission remains CONFIRMED and an applicable Period 1 demand is created automatically when effective fee items exist.
4. Verify the demand contains University + College applicable items and exact period snapshots for amount, Mandatory, Enrollment Clearance Required and Installment Allowed.
5. Confirm an Admission whose offering has no applicable active Period 1/ONE_TIME fee items. Expected: confirmation succeeds and no demand is created.
6. For an older CONFIRMED Admission without Period 1 demand, use Manual Recovery and verify one demand is generated; repeat is blocked.
7. Revoke a confirmed Admission with untouched demand. Expected: demand auto-cancels. Paid/adjusted demand must block revocation.
8. Do not implement or QA later-period bulk execution until Student Enrollment + authoritative Academic Progression result persistence exists. Next domain work after initial Fee Demand QA is Payment / Adjustment / Installment + Fee Clearance, followed by Enrollment/Lifecycle; later bulk demand then consumes progression output.

### Immediate QA — Fee Demand Test Data Cleanup
1. Open Test Data Cleanup → Fee Management and confirm `Fee Demands` is visible before Fee Structures.
2. Select the Ashutosh test demand generated during Manual Recovery and clean it using the existing confirmation-code flow.
3. Confirm its Fee Demand Item rows are removed and the demand disappears from Fee Demands.
4. Confirm the Admission remains present and can then be cleaned separately if desired.
5. Confirm Fee Structure / Fee Head cleanup is no longer blocked by that deleted demand item when no other downstream refs exist.
6. Later, after Payment/Adjustment implementation, verify a financially-used demand is shown as blocked and cannot be removed until downstream test financial records are reversed/cleaned.

## QA checkpoint — ADR 117 Fee billing-context integrity
1. Create/keep an INACTIVE Academic-Year-wise Fee Structure and add a Fee Item only to Academic Year 1.
2. Open Edit Fee Structure: Fee Collection Basis must be locked while the Fee Item exists.
3. Remove the Fee Item, then edit the structure and change basis to Every Semester.
4. Confirm Semester 1 and Semester 2 start with no Fee Items; add the charge only to Semester 1.
5. Confirm Semester 2 remains empty and Configured Total includes only explicitly configured periods.
6. Repeat reverse direction Semester-wise → Academic-Year-wise if desired; no automatic copying is allowed.

## Resume checkpoint after ADR 118
1. Run migration `2026_09_05_200000_add_context_to_fee_demands.php`.
2. QA Admission Initial purpose filtering: ADMISSION charges + only ACADEMIC Enrollment-Clearance charges; no EXAMINATION/OTHER leakage.
3. QA Program Offering bulk contexts against Fee Setup:
   - PER_TERM → real Curriculum Semester/Trimester/Term labels,
   - PER_ACADEMIC_YEAR → Academic Year labels,
   - specific period → only configured period,
   - ONE_TIME → one-time context.
4. QA first Academic period bulk generation for the full CONFIRMED offering cohort and verify already-demanded clearance items are not duplicated.
5. Verify Semester/Year 2+ remains blocked with the resolved University Academic Policy shown until Student Enrollment + Academic Progression is implemented.
6. Continue downstream with Payment / Adjustment / Installment, Fee Clearance, then Student Enrollment. When Academic Progression becomes authoritative, switch later-period bulk eligibility from blocked to progression-result consumption without duplicating policy rules in Fee Management.

### Fee Demand QA — refundability
- Generate a fresh demand containing one refundable and one non-refundable Fee Head and verify the item badges.
- Change the Fee Head after demand creation only in a safe test state and verify the already-generated demand snapshot does not change.
- Future Payment/Refund implementation must consume `fee_demand_items.is_refundable` plus payment/refund-policy state; it must not treat the flag alone as refund authorization.

## Immediate QA — ADR 120 Bulk + Individual Fee Demand
1. Keep the current BA Fee Setup active and choose a first Academic billing context derived from Fee Setup.
2. Switch `Generate For` to `Individual`, select one CONFIRMED admission from the offering, and generate.
3. Verify exact amount + Mandatory + Enrollment Clearance + Installment + Refundable snapshots.
4. Retry the same Individual action: no duplicate active charge may be created.
5. Run Bulk for the same context: the individually-demanded candidate must be skipped for already-demanded source items while any other eligible candidates are generated.
6. Reverse the order in a clean test: Bulk first, then Individual; Individual must not duplicate the existing source items.
7. Select Semester/Year 2 while progression is unavailable: both modes must remain blocked.
After this passes, continue Fee Demand purpose/basis QA, then Payment / Adjustment / Installment, Fee Clearance, and Student Enrollment.

## Immediate QA — ADR 121 compact Fee Demand register
1. Open College → Fee Management → Fee Demands with Ashutosh having both Semester 1 and Academic Year 1 demands.
2. Confirm Ashutosh appears only once in the top-level register with `Demands (2)` and aggregate totals (for the current test data: ₹25,000 total, ₹25,000 mandatory, ₹20,000 enrollment clearance, ₹25,000 outstanding).
3. Confirm `Demands (2)` is closed on initial page load. Open it and verify Semester 1 and Academic Year 1 remain separate child demands.
4. Confirm each child demand's Fee Items are also closed until `View Details` is clicked.
5. Search by candidate name, Application No., Admission No., Demand No. and `Academic Year 1`/`Semester 1`; the matching candidate group must remain discoverable.
6. Confirm Manual Recovery is absent from the normal Fee Demand screen while Bulk Cohort and Individual generation remain unchanged.
7. With more than 10 candidate groups, confirm Previous/Next pagination limits the register to 10 admissions per page.
After this UI QA passes, continue the remaining Fee Demand purpose/basis/refundability QA before Payment / Adjustment / Installment implementation.

### Fee Demand QA — searchable Individual selector (ADR 122)
1. Select an eligible first billing period and switch Generate For to Individual.
2. Type fewer than 2 characters: no server result list should be selectable.
3. Search by candidate name, Application No., and Admission No.; matching confirmed candidate must resolve.
4. Select the candidate and generate; existing duplicate and eligibility protections must remain unchanged.
5. Verify a candidate from another Program Offering is never returned.

### Immediate QA — ADR 123 mutation outcome feedback
1. Select a first-period Fee Demand already generated for Ashutosh and run Individual generation again.
2. Confirm no duplicate demand is created and a visible info toast states that all applicable fee items are already covered by active demand(s).
3. Run Bulk for the same fully-covered cohort and confirm a visible info toast reports `no new demands generated` plus the skipped count.
4. In a clean/mixed cohort, confirm Bulk reports both generated and skipped counts when some candidates are new and some are already covered.
5. Confirm a successful fresh Individual generation still produces the normal success toast.
6. Treat any future mutating action that silently redirects/no-ops without visible feedback as QA FAIL under ADR 123.


### Fee Demand QA resume — toast context fix
- Re-test the already-covered Individual Demand action.
- Expected: no duplicate demand is created and an informational toast is visible.
- Confirm the Fee Demand page remains rendered (no `usePage must be used within the Inertia component` error).

### QA checkpoint — visible mutation outcome feedback (ADR 125)
1. Fee Demand -> Individual -> choose an already-demanded candidate/period -> Generate.
2. Expect no duplicate demand and an INFO toast explaining that all applicable fee items are already covered.
3. Generate a genuinely new individual/bulk demand -> expect SUCCESS toast.
4. Trigger a validation failure (for example submit a required mutation field empty) -> expect ERROR toast with the validation message.
5. Confirm the page never blanks and the global Toaster remains mounted through the project theme.

## Current next workflow (2026-09-07)
1. QA Scholarship / Concession / Waiver Foundation (ADR 126).
2. Implement candidate/student Benefit Allocation + Approval against Fee Demand.
3. Implement Installment scheduling.
4. Implement Payment Collection + Allocation + Student Fee Ledger.
5. Implement Adjustment/Reversal/Refund and Fee Clearance.
6. Enrollment consumes Fee Clearance; it must not depend directly on payment mode.

### Immediate QA checkpoint — Scholarship / Benefits cleanup (ADR 127)
1. Open Test Data Cleanup → Fee Management → Scholarship / Benefits.
2. Create/retain one University scheme and one College scheme and confirm both are listed.
3. Clean one test scheme; expect a success outcome and removal of that scheme only.
4. Verify referenced Fee Heads and Reservation Categories still exist.
5. After cleanup QA passes, resume Scholarship / Concession / Waiver Foundation QA (ADR 126), then student Benefit Allocation + Approval.

## Next QA — Scholarship Academic Session Context (ADR 128)
1. University: verify only sessions with current ACTIVE + APPROVED Curriculum appear.
2. Verify eligible Academic Session marked Current is preselected and labelled Current.
3. College: verify only sessions with ACTIVE Program Offerings backed by current ACTIVE + APPROVED Curriculum appear.
4. Change College session and verify Program Offering list contains only that session's eligible offerings.
5. Attempt stale/invalid session/offering submission and verify controlled validation feedback.

### Scholarship / Benefits UI QA note — ADR 129
- Verify University and College scheme cards render project-consistent Pencil/Edit, Power/Activate and PowerOff/Deactivate controls according to status and RBAC.


## After ADR 130
QA Student Benefit flow in this order: demand search → eligible scheme resolution → reservation mismatch protection → manual PENDING assignment → approve lower/equal calculated sanction → Fee Demand adjusted/outstanding verification → reject/no financial effect → automatic scheme auto-sanction → duplicate protection → RBAC → Test Data Cleanup reversal. After QA PASS, implement Installment / Payment Collection / generic Adjustment-Reversal / Fee Clearance before Enrollment.

### Scholarship QA prerequisite — Candidate Category
Before automatic scholarship processing, QA ADR 132: mapped SC candidate on OPEN seat remains SC for benefit eligibility; unmapped form requires manual candidate-category confirmation; General candidate is not eligible for SC-only scheme.

### Immediate regression QA — Seat Allocation guard + toast (ADR 133)
1. Open a candidate whose ACTIVE Seat Allocation is linked to a `CONFIRMED` Admission; `Cancel Allocation` must be disabled and explain that Admission must be revoked first.
2. Confirm direct/stale cancellation still fails server-side and the validation failure is surfaced through the project error toast.
3. Revoke the Admission through the valid Admission workflow; return to Seat Allocation and verify cancellation becomes available.
4. Cancel the allocation with a reason; expect the existing success toast and released physical capacity.
5. Re-test ADR 132 Candidate Reservation Category mapping/manual fallback to confirm this regression fix did not remove the new category-source behavior.

### QA — ADR 135 Seat Allocation category / merit priority
1. Mapped SC candidate: SC auto-resolves; SC physical bucket is recommended when capacity remains.
2. SC bucket full: OPEN becomes fallback only if OPEN capacity remains; backend must still enforce OPEN merit priority.
3. General candidate: SC/ST/OBC/EWS physical buckets are disabled and backend rejects tampered reserved requests.
4. SC candidate: ST/OBC/etc. reserved buckets are disabled and backend rejects cross-category requests.
5. Lower-ranked candidate attempting OPEN while a higher-ranked VERIFIED + ELIGIBLE candidate is unallocated must receive visible validation toast and no seat consumption.
6. Same-category lower-ranked candidate cannot bypass a higher-ranked mapped candidate for that reserved bucket.
7. Reconfirm Admission Confirmation cancellation guard and toast consistency remain intact.

### Immediate QA — ADR 136 application category auto-resolution
1. Submit an application with system-mapped Candidate Reservation Category = SC.
2. Open Seat Allocation for that candidate; SC must be auto-selected and locked with no manual-resolution warning.
3. Verify SC physical seat is recommended while SC capacity remains; if SC is full, ADR 135 OPEN merit/capacity rules control fallback.
4. Submit/inspect a General application; `General / Unreserved` must auto-resolve without requiring a General reservation quota in Intake.
5. Confirm PwD/Sports or any HORIZONTAL master category never appears in the Candidate Reservation Category field.
6. Test an older/unmapped application; only that case should show manual candidate-category confirmation.
### Immediate QA — ADR 137 Bulk Student Benefit
1. Open Student Benefits and confirm Individual mode remains unchanged.
2. Switch to Bulk Assignment; only ACTIVE College/University schemes valid for the College must appear.
3. Select an SC/ST/etc. category-based scheme; verify only matching candidate-category Fee Demands are eligible to consider.
4. Select an OPEN/merit-type scheme; verify eligibility does not auto-select or auto-award students.
5. Verify hierarchy is initially compact/collapsed and expands exactly Degree Level → Degree → Discipline → Student.
6. Verify Degree Level, Degree and Discipline labels/names/codes differentiate academic groups correctly.
7. Test parent checkbox selection at Degree Level, Degree and Discipline, then deselect individual students; only final checked students must be submitted.
8. Verify student row context: Candidate, Application/Admission No., Demand/Billing Period, Candidate Category, Eligible Base and Calculated Benefit.
9. Search by student, application/admission/demand, Degree Level, Degree, Discipline and category; tree/count should update without losing valid selected records.
10. Verify duplicate same-scheme PENDING/APPROVED demands are excluded and shown in Already Assigned count.
11. MANUAL scheme: assign a mixed subset; all successful rows become PENDING and Fee Demand financials remain unchanged until approval.
12. AUTOMATIC scheme: selected valid rows auto-sanction and update adjusted/net outstanding without mutating original gross amount.
13. Create a stale eligibility condition between preview and submit; backend must re-check each demand, skip only invalid rows, and never apply benefit incorrectly.
14. Verify summary totals/counts and Student Benefit Register after bulk action.
15. Re-run individual assignment, approval/reject, RBAC and Test Data Cleanup regression before Student Benefits is marked QA PASS.


### Student Benefits QA — ADR 137 initial-load hotfix
1. Open `Student Scholarship / Concession / Waiver` and switch to `Bulk Assignment` before selecting any scheme.
2. Confirm no stray `0` or `000` text appears under the Benefit Scheme row.
3. Confirm all ACTIVE University schemes inherited by the College and ACTIVE College-owned schemes appear in the Benefit Scheme dropdown immediately.
4. If no ACTIVE scheme exists, confirm the explicit `No ACTIVE University or College benefit scheme...` empty state is shown.
5. Select a scheme and continue full ADR 137 QA for candidate resolution, Degree Level → Degree → Discipline grouping, controlled selection, preview totals, duplicate protection, and final revalidation.

### Immediate QA — ADR 138 Bulk candidate loading + Remove Benefit
1. Open Bulk Assignment and select each ACTIVE scheme. Confirm eligible Fee Demands load instead of silently showing no students.
2. Confirm hierarchy uses Degree Level → Degree → the student's Admission Academic Preference Discipline → Student.
3. For a scheme with no genuinely eligible Fee Demands, confirm the normal `No currently eligible...` state; for a server/query failure, confirm a visible error message is shown instead.
4. Assign one MANUAL benefit so it remains PENDING. Enter a removal reason, click Remove, confirm, and verify status becomes CANCELLED with no Fee Demand adjusted/outstanding change.
5. Assign or use one AUTOMATIC/APPROVED benefit. Record Fee Demand total, adjusted and outstanding, then Remove Benefit with a reason.
6. Confirm approved removal reverses exactly that benefit's sanctioned amount from `adjusted_amount`, recalculates outstanding/status, and never changes gross `total_amount`.
7. Confirm the removed benefit remains in Student Benefit Register as CANCELLED for audit and is not physically deleted.
8. Re-test same scheme against the same demand after removal; eligibility/duplicate logic must no longer treat the CANCELLED record as active.
9. Re-run ADR 137 controlled bulk selection, MANUAL approval, AUTOMATIC sanction, duplicate protection and category eligibility regression.

### Immediate QA — ADR 139 Individual clear + eligibility diagnostics
1. Search and select Amit (or any Fee Demand), then click `Clear selection`; the staged card, scheme selection and search value must reset, while no Student/Admission/Fee Demand data is deleted.
2. Select a demand, then type a different student/demand in the search field; the previous selection must clear automatically and new search results must be usable.
3. For a demand where schemes are ineligible, verify each disabled option shows the concrete reason and the compact `Why no scheme is eligible` panel matches backend eligibility.
4. Confirm a Reservation Category scheme reports category mismatch only when the candidate-category snapshot genuinely does not match the scheme categories.
5. Confirm a Fee Head-scoped scheme reports `Selected Fee Demand has no fee item covered by this scheme` when that demand does not contain any selected scheme Fee Head.
6. In Bulk Assignment select the same scheme. If no eligible rows exist, verify Scanned / Ineligible / Already Assigned counts plus aggregated exclusion reasons are shown.
7. Confirm Bulk still lists only genuinely eligible rows and does not loosen eligibility merely to make candidates appear.
8. Re-run one eligible Individual assignment and one eligible Bulk controlled-selection assignment to confirm calculation, approval mode, duplicate protection and final revalidation remain unchanged.

### Immediate QA — ADR 140 Individual active duplicate visibility
1. Use the Amit + College Merit Concession test record currently in `PENDING` state.
2. Search/select the same Academic Fee Demand in Individual mode.
3. Confirm `College Merit Concession 2026` remains visible but is disabled and labelled `Already assigned — PENDING`.
4. Confirm the same Fee Demand itself is still searchable/selectable and any other genuinely eligible, unassigned scheme remains selectable.
5. Approve the pending College Merit benefit, reopen the same demand, and confirm the option changes to `Already assigned — APPROVED`.
6. Confirm direct/stale duplicate submission is still rejected server-side even if the UI is bypassed.
7. Later, after cancelling/removing the benefit, confirm the CANCELLED record no longer blocks re-assignment and the scheme returns to normal eligibility evaluation.
8. Re-check Bulk Assignment for the same scheme: the demand must remain excluded from eligible rows and contribute to `Already assigned` while PENDING/APPROVED.

### Student Benefits QA — Fee Demand audit visibility (ADR 141)
- [ ] After approving Amit's College Merit Concession for ₹200 against the ₹2,000 Semester 1 demand, verify Gross Demand ₹2,000, Paid ₹0, Scholarship/Concession/Waiver ₹200, Outstanding ₹1,800.
- [ ] Verify Approved Student Benefits lists College Merit Concession 2026 (COL-MERIT-2026) at ₹200.
- [ ] Verify Library Fee item shows Benefit −₹200 without changing the original item amount ₹2,000.
- [ ] Then remove/reverse the approved benefit and verify the active benefit disappears, adjustment returns to ₹0, and Outstanding returns to ₹2,000.

## Current next workflow — 2026-09-08 after Student Benefits QA PASS
1. **QA Fee Installment Scheduling (ADR 142) — CURRENT.**
2. Freeze and implement Late Fine / Penalty Rules against demand/installment due dates.
3. Implement Payment Collection + Allocation, including the committed pre-integrated plug-and-play online payment gateways with institution credentials/configuration.
4. Implement Student Fee Ledger.
5. Implement generic Adjustment / Reversal / Refund.
6. Implement Fee Clearance.
7. Mark Fee Phase QA PASS, then resume Student Enrollment / Student Lifecycle; Enrollment consumes Fee Clearance and must not depend directly on payment mode.

Standing cleanup rule: every future module that creates QA/test transactional data must add the necessary Test Data Cleanup coverage at implementation time. Preserve real/master configuration unless it was explicitly test-only.

### Immediate QA — ADR 142 Installment Scheduling
1. Fee Demand → expand a demand containing an item with `Installment Allowed = Yes`.
2. Click **Set Installments**. Confirm gross item, approved benefit and net-to-schedule are shown separately.
3. Enter at least 2 chronological installments totaling exactly the net payable and save.
4. Reload and confirm schedule persists; gross demand/item amount remains unchanged.
5. Verify `Installment Allowed = No` items do not expose schedule management.
6. Verify incorrect total and non-chronological due dates are rejected.
7. Replace schedule before collection and verify new ACTIVE schedule with prior audit history retained as CANCELLED.
8. Test Data Cleanup → Fee Management → Installment Schedules → clean the test schedule and verify the complete item schedule is removed.
9. After QA PASS, implement Late Fine Rules. Do not jump to Student Lifecycle.

### Revised Immediate QA — ADR 142 common/bulk + individual Installment Scheduling
1. Select Program Offering + executable billing context in Fee Demand, then open **Bulk Installment Schedule** and load eligible students.
2. Verify only Fee Demand Items snapshotted with `Installment Allowed = Yes` appear; verify hierarchy and discipline grouping.
3. Select a Fee Head; verify eligible students default selected and can be deselected individually or by discipline group.
4. Create a common 2+ installment percentage schedule totaling exactly 100% with chronological due dates and apply it to selected students.
5. Expand two affected Fee Demands and verify monetary installments equal each student's current net payable; if one has an APPROVED benefit, verify its amounts use the reduced net payable while gross demand remains unchanged.
6. Verify an existing schedule is visibly flagged in bulk preview and can be replaced before collection, retaining CANCELLED history/audit.
7. Verify a student/demand with collection started is blocked from bulk replacement.
8. Verify Individual **Set/Manage Installments** still works as an authorized exception and `Installment Allowed = No` never exposes scheduling.
9. Verify invalid percentage total / non-chronological dates are rejected server-side.
10. Test Data Cleanup → Fee Management → Installment Schedules must remove QA schedules/history as documented without removing real Fee Setup.
After ADR 142 QA PASS, freeze/implement Late Fine Rules next; do not jump to Student Lifecycle until remaining Fee Phase is complete.

## ADR 158 status update — 2026-09-09
Late Fine / Penalty Rules are now IMPLEMENTED and awaiting QA. Do not begin Payment Collection + Allocation until ADR 158 QA is accepted. After Late Fine QA PASS, Payment Collection + Allocation is the next Fee Phase implementation, followed by Online Payment Gateways, Student Fee Ledger, Adjustment/Reversal/Refund, Fee Clearance, then Student Enrollment / Student Lifecycle.

## ADR 160 Immediate QA — Fee Billing Period Standard Due Date
1. Run the ADR 160 migration and clear caches.
2. Deactivate one test Fee Structure and edit a Fee Head under a Billing Period.
3. Verify Standard Due Date is required for an ACTIVE charge and uses the project DatePicker.
4. For a recurring structure, save distinct Due Dates in two Billing Periods and verify they persist independently.
5. Verify Fee Structure activation is rejected if any ACTIVE applicable charge is still missing its Due Date.
6. Generate a fresh Fee Demand and verify each Fee Head carries the Standard Due Date snapshot.
7. Change the Fee Setup date later and verify the already-generated Fee Demand Item does not change.
8. Verify existing installment schedules and their own due dates remain unchanged.

After ADR 160 QA PASS: revise ADR 158 Late Fine source/scope so installment rows use installment due dates and non-installment charges use the Fee Demand Item Standard Due Date. Re-run Late Fine QA. Only after revised Late Fine QA PASS may Payment Collection + Allocation begin.

## Immediate QA order after ADR 161
1. QA Curriculum-linked Academic Period setup in University Academic Calendar.
2. QA period-scoped Examination event boundary validation and general event compatibility.
3. QA ADR 160 Standard Due Date inside/outside Curriculum period and demand snapshot.
4. Revise/QA ADR 158 Late Fine against finalized due-date hierarchy.
5. Only after Late Fine PASS: Payment Collection + Allocation.


### ADR 162 QA gate
Before continuing ADR 161 UI QA, rerun `php artisan migrate` with the ADR 162 patched migration and confirm it completes; then run `php artisan optimize:clear`.

## ADR 163 immediate QA — Academic Period selector
Before continuing Academic Period date/boundary QA:
1. Open University Academic Calendar -> Add Academic Period.
2. Search a Curriculum by Curriculum/Programme name or code.
3. Select the Curriculum; verify only its remaining unconfigured Semester/Year Terms appear.
4. Select a Term, set dates and save; verify exact Curriculum/Term is shown in the Academic Period register.
5. Re-open Add Academic Period and verify the configured Term is no longer selectable.
Then continue ADR 161 Academic Period boundary QA, followed by ADR 160 Fee Due Date integration QA and ADR 158 Late Fine revision/QA.

## Immediate QA gate after ADR 164
Do not proceed to Late Fine or Payment Collection yet.

Run Academic Calendar + Fee Setup linkage QA in this order:
1. Confirm a current APPROVED Curriculum has Academic Session plus Effective From/To.
2. Add a Curriculum Term Academic Period inside that effective window; it must save.
3. Attempt Start Date before Curriculum Effective From; it must block.
4. Attempt End Date after Curriculum Effective To; it must block.
5. Confirm blank Curriculum Effective boundary falls back to Academic Session boundary.
6. Open matching Fee Structure/Billing Period; Standard Due Date DatePicker must show/use the same Academic Period bounds.
7. Save an in-range Standard Due Date; it must save.
8. Attempt an out-of-range Due Date/direct request; backend must block.
9. Confirm missing/invalid Academic Period blocks recurring/specific-period Fee Due Date configuration.
10. Generate a Fee Demand and verify the Due Date snapshot on the exact Fee Demand Item.

After PASS, revise ADR 158 Late Fine to consume the finalized snapshot/installment due-date hierarchy, then QA Late Fine. Payment Collection + Allocation remains after Late Fine PASS.

### Immediate regression QA before Academic Period/Fee linkage QA
Run ADR 165 Test Data Cleanup route QA first: verify Clean Selected, Clean All Cleanable, and one individual Clean action no longer return 404. Then resume ADR 164 Academic Calendar → Curriculum Effective Window → Fee Setup linkage QA.

### Immediate QA — ADR 166 Admission Form Applicability
Before returning to Fee Due Date / Late Fine QA, verify Academic Applicability against at least one UG and one PG/program-offering mapping, including multi-select Program/Curriculum scope and Candidate Profile Photo Final Preview. Confirm UI visibility and backend required-field validation agree.


### Immediate QA — ADR 167
1. Open Add Academic Period.
2. Select a Curriculum whose effective/session boundary starts after the current browser year/month.
3. Open Start Date and End Date.
4. Confirm Year is populated and view is within the allowed boundary.
5. Change Curriculum and confirm both pickers resynchronise.
6. Then continue Academic Period → Fee Setup linkage QA.

- Re-run Academic Period DatePicker QA after ADR 168: verify Year is visibly shown in Start Date and End Date pickers, then continue Academic Period -> Fee Setup linkage QA.

### ADR 170 QA before Payment Collection
1. Verify two or more Fee Heads with the same snapshotted due date appear in one server due group.
2. Verify mandatory_due and optional_due are separate and optional is not promoted to mandatory.
3. Verify approved benefit reduces non-installment open amount.
4. Create installments for multiple heads: same installment due dates group together; parent item due date is not double-counted.
5. Verify different installment due dates create separate groups.
6. Verify paid installment amount reduces only that installment's open amount.
7. Verify Gross Fee Demand and Fee Demand Item amounts remain unchanged.
8. Then continue Late Fine QA for installment-backed liabilities; non-installment fine posting must wait for authoritative collection allocation/open-principal state.
9. After the due/late-fine gate passes, implement Payment Collection + Allocation using this grouping contract.

## Immediate Next — ADR 171 Payment Collection + Allocation QA
Run QA in this order:
1. Migrate and confirm Payment Collection menu/permissions.
2. Same due date, multiple mandatory Fee Heads → one combined collection; verify head-wise allocations.
3. Same due date with optional Fee Head → default excludes optional; opt-in includes it.
4. Partial payment → verify oldest-due + mandatory-first allocation and remaining Due Group balance.
5. Installment payment → exact installment `paid_amount` changes; future installment remains untouched.
6. Late Fine included → principal and fine allocate separately; Fee Demand Gross remains unchanged.
7. Late Fine excluded → fine stays payable while principal payment posts.
8. Overpayment → rejected atomically with no receipt/allocation.
9. Verify Fee Demand `paid_amount`, `outstanding_amount`, status and Payable incl. Fine.
10. Re-run Late Fine calculator after a fine has payment allocation → paid fine revision must not be silently reversed/superseded.
11. Test Data Cleanup → clean payment and verify installment/demand balances are restored; Fee Setup and rules remain.
12. Only after QA PASS move to Online Payment Gateways.

### Payment Collection QA pre-check
Before functional ADR 171 QA, confirm ADR 172 layout consistency: one application shell/header only. Then continue payment allocation QA.

## Immediate QA gate — ADR 173
Before Online Payment Gateways, QA Payment Collection with the student-grouped register and Due-Now contract: grouped Amit Kumar row -> expand demands -> verify future 20-Sep installment excluded by default on 10-Sep -> explicit Include future dues -> partial posting -> exact allocation -> cleanup/RBAC regression.

### ADR 174 QA
1. Confirm grouped student row shows earliest future due date + amount.
2. Expand student and confirm each demand shows its own earliest future due.
3. Confirm future amount is not included in default Due Now.
4. Confirm Include future dues allows advance collection.
5. After advance payment, confirm paid future installment disappears and next future due advances automatically.
6. On/after due date, confirm it is no longer labelled future and participates in Due Now; Late Fine follows its configured grace/rule.

### Immediate QA — ADR 175 regression
1. Use a demand with Mandatory/Future Principal ₹20,000 and Optional Open ₹2,000.
2. Enable Include future dues + Include optional charges.
3. Post ₹21,000.
4. Verify allocations consume ₹20,000 mandatory principal first and only ₹1,000 optional principal.
5. Verify remaining state is Mandatory/Future Principal ₹0 and Optional Open ₹1,000.
6. Reopen Collect Payment and confirm Payment Amount resets to the current selected available amount (default optional excluded should be ₹0; after Include optional charges it should become ₹1,000), never the stale ₹21,000.
7. Only after PASS continue Payment Collection cleanup/RBAC and remaining ADR 171 QA.

## Immediate QA — ADR 176
1. Open Late Fine / Penalty Rules and confirm compact Pencil, Power and Trash actions.
2. On the current ACTIVE QA rule, confirm Edit/Delete are disabled; use Power to deactivate it.
3. Confirm Edit becomes available while INACTIVE.
4. If the rule still has Late Fine Charge history, attempt Delete and confirm it is blocked with the history/cleanup message.
5. Clean the related QA Late Fine Charges using Test Data Cleanup, then delete the now-unused INACTIVE QA rule and confirm it disappears.
6. Confirm Due Date / As Of values are human-readable and no raw ISO timestamp is shown.
7. Then run Payment Collection Test Data Cleanup QA (receipt/allocation removal + balance restoration).
8. Only after that cleanup QA passes, begin Online Payment Gateway implementation.

## Next after ADR 177 QA
1. QA Gateway Configuration lifecycle and RBAC.
2. QA Fee Head product/settlement mapping, including shared product-code use.
3. Implement provider transaction foundation + Razorpay adapter first: create order/checkout, signature/webhook verification, idempotency and verified-success handoff to existing FeePaymentService.
4. Then Cashfree adapter, then PayU adapter against the same provider contract.

## Immediate QA — ADR 178
1. Enter/change Product Code mappings for at least two Fee Heads and click `Save All`.
2. Refresh and confirm all submitted mappings persist.
3. Save one mapping individually and confirm `Fee Head mapping saved.` toast appears.
4. Save a second mapping individually and confirm the same toast appears again (global repeated-toast regression).
5. Continue ADR 177 QA: same Product Code on multiple Fee Heads, gateway credential/edit lifecycle, activation guard, RBAC.
6. After ADR 177/178 QA PASS, implement provider transaction foundation + Razorpay adapter against the existing FeePaymentService.

### Immediate QA after ADR 179
1. On Payment Gateway Configuration, keep Razorpay Test without Key ID/Secret.
2. Click Activate.
3. Expect to remain on the same ERP page, receive error toast `Key ID and secret are required before activation.`, and gateway remain INACTIVE.
4. After PASS, continue Payment Gateway configuration QA before implementing Razorpay transaction initiation/verification.

## Immediate QA — ADR 180
1. Run the ADR 180 migration with the existing Razorpay Test profile in place.
2. Add a second Razorpay TEST credential profile (different Profile Name) and confirm both profiles coexist.
3. Open/edit both and verify credentials are independently stored/masked.
4. Make one Fee Head ACTIVE on profile A, then save the same Fee Head ACTIVE on profile B; confirm A becomes INACTIVE for that Fee Head while B remains ACTIVE.
5. Confirm unrelated Fee Heads remain mapped to their intended profiles and shared Product Code behavior still works.
6. Re-run ADR 179 activation guard: a profile without Key ID/Secret must stay INACTIVE and show an in-app error toast.
7. After PASS, implement the provider transaction foundation + Razorpay adapter. Provider checkout must resolve the correct ACTIVE credential profile from the Fee Head(s); do not create parallel accounting logic.

### Immediate QA after ADR 181
1. Re-run `php artisan migrate` and confirm `2026_09_10_181000_allow_multiple_gateway_credential_profiles` completes.
2. Confirm existing Razorpay Test profile remains intact.
3. Create a second Razorpay TEST credential profile for the same college/provider and verify both profiles coexist.

## After ADR 182
1. QA independent Fee Head routing with at least two TEST credential profiles.
2. QA unassigned Fee Head and TEST/LIVE separation.
3. QA Product Code validation toast.
4. Once routing QA passes, implement Razorpay order/payment verification + webhook adapter reusing existing FeePaymentService/allocation engine.

### Next after ADR 183
1. QA provider-specific configuration/routing behavior.
2. Implement common online-payment attempt/transaction state model and adapter interface.
3. Implement Razorpay adapter first using official Orders/API authentication + signature verification/webhook flow.
4. Implement Cashfree adapter using App ID/Secret + Create Order/payment-session/status/webhook flow.
5. Implement PayU adapter using Merchant Key/Salt + request/reverse hash/verify flow.
6. Implement NTT DATA/Atom only when required credentials/UAT contract are available; honor Merchant ID/Login/Password/Product ID and provider encryption requirements.
7. Only VERIFIED/SUCCESS provider transactions may post into existing Fee Payment + deterministic allocation + receipt engine.
8. Add Test Data Cleanup coverage for gateway attempts/callbacks before Online Payment Gateway QA PASS.
