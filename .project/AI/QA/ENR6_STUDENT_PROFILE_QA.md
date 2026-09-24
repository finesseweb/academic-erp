# ENR-6 Student Profile — Owner QA

Status: PENDING

1. Migrate; confirm Student Management shows Student Profile for an authorized role.
2. Verify Session → Programme Offering filters and search; verify server pagination.
3. Open one Admission-source Student and one Import-source Student. Both must render through the same profile page.
4. Verify Student UID, University Roll, Class Roll, Curriculum/Discipline and courses are displayed but cannot be edited here.
5. Edit full name/DOB/email/phone; save; verify toast and persistence.
6. Edit stored STUDENT_PROFILE fields; verify APPLICATION_ONLY fields do not appear.
7. Verify SELECT/RADIO/YES_NO and multi-value fields persist correctly; required stored profile fields reject blank values.
8. Verify FILE/IMAGE profile values remain read-only.
9. Verify `student.profile.updated` audit entry includes actor, college scope, before/after and IP.
10. Verify view-only role cannot mutate and direct PATCH returns 403.
11. Regression: Student Identity assignment and Student Enrollment remain unchanged.

Closure gate: OWNER QA PASS before ADR 208 / ENR-6 is marked CLOSED.

## ENR-6.1 targeted QA
- [ ] Existing student with stored reservation category `GENERAL` renders `General / Unreserved` selected.
- [ ] Reservation dropdown contains ACTIVE VERTICAL University categories and excludes duplicate general/open aliases, matching Admission Form semantics.
- [ ] Saving without changing reservation category preserves `GENERAL` (does not rewrite it to `GEN`).
- [ ] Reserved category can be displayed/saved using its authoritative University category code.
- [ ] Academic Enrollment shows Curriculum name/code and does not expose raw Curriculum ID.
- [ ] Existing Student UID, University Roll, Class Roll, Discipline and canonical course choices remain unchanged.

## ENR-6.2 targeted QA
- [ ] Admission-copied DOB renders in the shared project DatePicker and saves as the same `YYYY-MM-DD` when unchanged.
- [ ] Profile header shows Programme, Session, Discipline and Class Roll without a duplicate bottom Academic Enrollments card.
- [ ] Academic summary is grouped by authoritative Course Category and contains only `APPLICANT_CHOICE` selections; `AUTO_MANDATORY` courses are not presented as student choices.
- [ ] Package-style choices collapse repeated term papers to the human-facing source Discipline under the category; genuine course-level choices show the resolved Course name.
- [ ] If the Admission Form governed a `CANDIDATE_PROFILE_PHOTO` as `STUDENT_PROFILE`, the copied photo renders in the header through a permission-gated private-file endpoint.
- [ ] Authorized editor can replace the profile photo; refreshed profile shows the replacement and `student.profile.photo.updated` is audited.
- [ ] Replacing a photo does not delete the original Admission-owned file; replacing a previous Student-owned photo cleans only that prior Student-owned file.
- [ ] View-only role can view the photo but cannot upload a replacement; direct upload returns 403.
- [ ] Student Identity and canonical Enrollment/course-choice rows are unchanged by profile edits/photo replacement.

## ENR-6.3 targeted QA — Import photo parity
- [ ] Open an IMPORT-source Student whose current Programme Offering has an applicable ACTIVE Admission Form Profile Picture field governed as `STUDENT_PROFILE`; the profile header shows an empty photo placeholder even though no photo value row exists yet.
- [ ] Authorized editor sees `Add Photo`, uploads a valid image, and refresh/reopen preserves it.
- [ ] First upload creates exactly one `student_profile_values` row using the configured photo field/profile key; a second upload updates the same row rather than creating a duplicate.
- [ ] After first upload the action label becomes `Update Photo` and `student.profile.photo.updated` is audited.
- [ ] Admission-source Student photo inheritance/update still works unchanged.
- [ ] IMPORT source does not require a photo column in CSV; FILE/IMAGE fields remain excluded from CSV mapping/required-field validation.
- [ ] If the current Programme Offering has no applicable governed Candidate Profile Photo field, no photo slot/upload capability is shown.
- [ ] View-only role can see an existing photo/placeholder but cannot upload; direct upload remains 403.

## ENR-6.4 regression QA
- [ ] IMPORT student on an offering whose applicable STUDENT_PROFILE template contains Candidate Profile Photo shows the photo placeholder even with no stored photo value.
- [ ] IMPORT student with no photo shows **Add Photo**; upload persists and changes action to **Update Photo** after reload.
- [ ] ADMISSION student inherited photo still renders and remains replaceable.
- [ ] Degree-scoped form mappings resolve when Student Profile eager-loads only display columns from Program Template.
- [ ] No Student/Enrollment/Admission provenance data changes while adding/updating profile photo.

## ENR-6.5 regression QA — immediate photo refresh
- [ ] Updating an existing Student Profile photo replaces the visible image immediately after successful upload; no manual browser refresh is required.
- [ ] Adding a first photo continues to render immediately.
- [ ] Repeated replacements change the versioned private-file URL while preserving the same canonical `student_profile_values` row.
- [ ] Admission and Import photo behavior remains identical after the UI refresh correction.
