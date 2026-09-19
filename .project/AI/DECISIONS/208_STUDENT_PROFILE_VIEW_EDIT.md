# ADR 208 — Student Profile View/Edit (ENR-6)

Status: IMPLEMENTED / OWNER QA PENDING
Date: 2026-09-18

## Decision
Student Profile is a permanent Student-centric view/edit surface under Student Management. It consumes `students`, governed `student_profile_values`, and canonical `student_enrollments` academic context. Admission and Import are provenance only and never create separate profile behavior.

## Boundaries
- Core editable Student data: full name, DOB, email, phone.
- Student UID / University Roll remain read-only here and are owned by Student Identity.
- Class Roll and academic context remain Enrollment-owned and read-only here.
- Dynamic fields are only values already governed as `STUDENT_PROFILE`; `APPLICATION_ONLY` data is excluded.
- FILE/IMAGE profile snapshots are visible but read-only in ENR-6.1; no unsafe replacement/upload path is introduced.
- Canonical enrollment courses are displayed from `student_enrollment_course_choices` + `courses`.
- No branching by `source_type` for academic truth.

## RBAC / Audit
`college_student_profile.view` controls list/detail visibility. `college_student_profile.edit` controls profile mutation and is sensitive. Successful mutations emit `student.profile.updated` with before/after snapshots. Identity and academic-context mutation are deliberately excluded.

## Scale
The list is server-paginated (25/50/100) and filters by Session → Programme Offering plus search. Detail loading is per Student; profile/enrollment/course data is loaded only when opening one Student.

## ENR-6.1 QA correction — Reservation Category + Curriculum presentation (2026-09-18)
- Student Profile MUST reuse the same authoritative `CANDIDATE_RESERVATION_CATEGORY` option semantics as Admission Form runtime/validation.
- Canonical open/unreserved value remains `GENERAL` with label `General / Unreserved`; it is not derived from a `GEN`/general-like Reservation Category master row.
- Other choices come from ACTIVE, VERTICAL University Reservation Categories, excluding general/open aliases exactly as Admission Form does.
- Existing `student_profile_values.value_text = GENERAL` is valid canonical data and MUST NOT be rewritten to `GEN` merely for display.
- Academic Enrollment presentation MUST show human-readable Curriculum name/code; database `curriculum_id` remains internal and is not a user-facing identifier.
- No schema/data migration is introduced by this correction.

## ENR-6.2 QA correction — Profile-first layout, photo lifecycle, DOB/date parity, academic-choice summary (2026-09-18)
- The Student Profile header is the concise current-enrollment summary. A duplicate bottom Academic Enrollments card is not shown on this profile surface.
- Current Programme, Session, Discipline and Class Roll are shown in the header; internal Curriculum IDs, mapping IDs and provenance implementation details are not user-facing.
- Academic choices are derived only from canonical `student_enrollment_course_choices` rows with `selection_source = APPLICANT_CHOICE`, joined back to Curriculum Slot → Course Category → Curriculum Course Mapping. They are presented under the authoritative Course Category name. `AUTO_MANDATORY` rows remain canonical data but are intentionally not shown as student choices on the profile.
- When a choice mapping has a source Discipline, the profile presents that human-facing source under its Course Category; genuine course-level choices without a source Discipline fall back to the resolved Course name. Duplicate term papers from one category/source are collapsed for the profile summary. No Enrollment/course data is rewritten.
- Core DOB MUST use the project shared `DatePicker`, not a browser-native date input. Server payload serializes Student DOB as `YYYY-MM-DD` so an Admission-copied DOB binds deterministically.
- A governed `STUDENT_PROFILE` field with `system_purpose = CANDIDATE_PROFILE_PHOTO` is promoted to the profile header. Its Admission-copied private file remains the initial Student photo. Authorized profile editors may replace it using the same field validation metadata and private `local` storage pattern.
- Student photo reads are permission-gated. Replacement writes to a Student-owned `student-profiles/{college}/{student}` path and emits `student.profile.photo.updated`. A copied Admission file is never deleted during replacement because it remains Admission provenance/history; only a prior Student-owned replacement may be cleaned up.
- No schema/data migration is introduced.

## ENR-6.3 QA correction — Import-source photo capability parity (2026-09-19)
- Profile-photo capability is determined by the applicable Admission Form configuration for the Student's current Programme Offering, not by whether that Student already has a copied photo value.
- Admission-source Students continue to inherit an existing governed `CANDIDATE_PROFILE_PHOTO` value when present.
- Import-source Students with the same applicable governed photo field render the same profile-photo slot in an empty state and may add a photo from Student Profile.
- The first Import-source upload creates the canonical `student_profile_values` row using the authoritative configured field ID/profile key; later uploads update that same row.
- Admission/Import `source_type` is provenance only and does not branch the downstream Student Profile capability.
- CSV import remains text/data oriented: FILE/IMAGE fields are not added to CSV mapping and a required profile-photo field does not block CSV import. Photo acquisition happens on Student Profile after import.
- No schema/reference-data migration is introduced.

## ENR-6.4 correction — IMPORT photo capability resolver
- Student Profile photo capability remains source-independent: ADMISSION and IMPORT students use the same governed STUDENT_PROFILE photo field.
- Root cause of the ENR-6.3 IMPORT failure was a partially-selected `programTemplate` relation (`id/name/code` only). `loadMissing()` did not reload `degree_id`, so degree-scoped Admission Form mappings could resolve as unavailable.
- `StudentImportService` now resolves `ProgramTemplate` authoritatively by `program_template_id`, including its Degree/degree-level context, before matching applicable Admission Form mappings and field scopes.
- No duplicate photo table, no source-type branch, and no CSV image-path contract were introduced.

## ENR-6.5 correction — immediate profile-photo refresh (2026-09-19)
- Replacing an existing profile photo previously reused the same permission-gated file route URL, allowing the browser to continue displaying the cached prior image until a manual refresh.
- Student Profile now versions the rendered private-file URL with the canonical profile-value `updated_at` timestamp. After a successful Add/Update Photo redirect, Inertia receives a changed image URL and the browser fetches the current image immediately.
- Storage, RBAC, audit, Admission provenance and the canonical `student_profile_values` ownership model are unchanged. No schema/reference-data migration is introduced.
