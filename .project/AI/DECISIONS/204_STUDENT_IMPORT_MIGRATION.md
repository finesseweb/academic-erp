# ADR 204 — ENR-4 Student Import / Migration

Status: IMPLEMENTED / OWNER QA IN PROGRESS — 2026-09-17

## Decision
Legacy/existing students enter the same authoritative `students` + `student_enrollments` lifecycle through a controlled College-scoped CSV workflow. Import never creates fake Admission/Application/Fee records. Imported rows use `source_type=IMPORT`; blank institutional identifiers remain Pending for the existing Student Identity issuance workflow.

## Workflow
Download template → upload CSV → select Session → Programme Offering → map columns → server validation → preview/errors → explicit confirmation → transactional import.

The uploaded file is temporary private storage scoped by College + actor + opaque token; no import staging/domain table is introduced. Import is blocked if any row is invalid, so a confirmed import is all-or-nothing.

## Academic context and Discipline
Session/Programme Offering are selected once for the import batch. ENR-4 adds nullable `student_enrollments.discipline_id` because imported legacy students have no Admission/Application chain from which Discipline can be derived. New Admission-origin enrollments also snapshot the authoritative Application Academic Preference discipline into this field. Historical Admission-origin rows remain compatible through the existing relationship fallback.

Student Identity uses Enrollment Discipline first and legacy Admission preference as fallback. This makes configurable Discipline-scoped Class Roll numbering valid for both ADMISSION and IMPORT provenance.

## Identity
CSV may contain existing Student UID, University Roll and Class Roll values. Uniqueness is validated before mutation. Class Roll validation follows the College's configured Class Roll scope. Blank identifiers are not auto-generated; they remain Pending for Student Management → Student Identity.

## RBAC / audit
Permissions: `college_student_import.view` and sensitive `college_student_import.manage`, grouped under Student Management. Successful import writes `student.import.completed` with actor, College, offering and row count. Backend authorization is authoritative.

## Database classification
Migration `2026_09_17_130000_enable_student_import_migration.php` is a Student-domain extension plus RBAC/reference-data migration. It adds one nullable FK/indexed column (`student_enrollments.discipline_id`) and two permissions. **No new domain table.**

## Scale
CSV parsing is streamed with `fgetcsv`; preview returns only the first 20 rows while every row is validated server-side. Import uses the same indexed Student/Enrollment tables and does not load a 50k-row browser preview.


## ENR-4.1 corrective QA note — upload validation compatibility
Owner QA found HTTP 500 on the initial CSV upload because the controller used `Rule::extensions()`, which is not available in the project's installed Illuminate validation API. This was an implementation compatibility defect, not a CSV-data failure.

The upload boundary now validates `required`, `file`, and the 5 MB limit through Laravel validation, then checks the client filename extension against the explicit allow-list `csv` / `txt` and returns a normal validation error for unsupported files. No database or domain-model change is involved. ENR-4 remains Owner QA in progress until the upload → mapping → preview → import path is verified.

## ENR-4.2 — Programme-aware Student Profile mapping (2026-09-17)
- Student Import now exposes ACTIVE Admission Form fields whose Data Usage is `STUDENT_PROFILE` and whose mapped template + academic field scope applies to the selected Programme Offering.
- `APPLICATION_ONLY` fields and FILE/IMAGE fields are not CSV profile targets.
- Imported profile values are written to the existing `student_profile_values` architecture with `source_application_field_id`, `profile_key`, and label snapshot; no parallel profile table is introduced.
- Programme Offering changes refresh the available Student Profile targets, so different offerings may expose different dynamic fields.
- Mapping UI provides per-target CSV-column search. Once a CSV column is selected for one target it is removed from the other target dropdowns; backend duplicate-column mapping validation is authoritative.
- Existing core import, identity-pending behavior, transactional import, RBAC, and audit behavior remain unchanged.
- Database classification: **no schema migration / no new domain table**. This change consumes existing Admission Form metadata and `student_profile_values`.
- QA status: implementation ready; Owner QA pending.

## ENR-4.3 — Drag-and-Plug Mapping + Saved Mapping Templates (2026-09-17)
Owner approved replacing the dense per-field search/dropdown mapper with a modern drag-and-plug workspace. Uploaded CSV headers are draggable plugs; Student/Core/Identity/Academic/Profile targets are sockets. A CSV header can connect to only one target; the UI locks a used header and the backend duplicate-mapping guard remains authoritative. Exact-name matches remain pre-connected automatically.

Mappings may now be named and saved per College + Programme Offering. A saved mapping can be loaded for later CSV batches; only headers actually present in the newly uploaded CSV are reconnected, so stale/missing headers are never silently fabricated. Saving under a new name creates an alternate mapping for the same Programme Offering; saving the same name updates that mapping.

Every saved mapping exposes a downloadable CSV template whose header row is derived from that mapping. This gives Colleges a stable reusable migration template after they have agreed the mapping once, while still allowing additional mapping variants for different legacy exports.

Database classification: migration `2026_09_17_140000_create_student_import_mappings.php` creates the supporting/configuration table `student_import_mappings` (College + Programme Offering + name + JSON mapping + actor metadata). This is **not a Student domain/entity table** and does not duplicate Student/Profile data. Imported Student data continues to persist only in the authoritative Student/Enrollment/Profile architecture.

QA status: IMPLEMENTED / OWNER QA PENDING. Required QA includes drag/drop connection/disconnection, one-use locking, exact-name pre-connect, save/load isolation by Programme Offering, alternate mapping names, generated template headers, and regression of Validate/Preview/Import.

## ENR-4.4 — Admission-linked Curriculum Academic Context (2026-09-17)

Owner direction: CSV migration is an alternate Student entry route, not an alternate academic model. Programme Offering academic context must therefore resolve from the same Curriculum rules used by the Admission Form.

Decision:
- Student Import derives Discipline, Specialization and curriculum-defined Choice categories from the selected Programme Offering's Curriculum through `ApplicantAcademicPreferenceService`.
- Mandatory curriculum mappings are auto-resolved exactly as in Admission. Only actual curriculum choices are imported from CSV.
- Import validation rejects Discipline/Specialization/Choice values that are not valid for the selected Programme Offering/Curriculum.
- Enrollment is the authoritative post-admission/post-import academic context. `student_enrollments` stores Curriculum + Discipline + Specialization and `student_enrollment_course_choices` stores resolved curriculum mappings/courses.
- Admission enrollment copies the already-resolved Application academic preference/course choices into the same Enrollment academic context. Import resolves directly into the same structure. This prevents Admission and CSV migration from diverging after Student creation.
- Mapping UI groups are accordion-based: Core Student opens by default; Identity, Academic Context and Student Profile remain collapsed until needed.
- Dynamic Student Profile fields continue to come only from applicable Admission Form fields marked `STUDENT_PROFILE`.

DB classification: lifecycle schema extension, not a duplicate domain model. Adds nullable `curriculum_id` and `specialization_id` to `student_enrollments`, plus `student_enrollment_course_choices` as the enrollment-owned resolved curriculum-course link table.

QA status: OWNER QA PENDING.

## ENR-4.4.1 migration recovery (2026-09-17)
Owner QA exposed MySQL identifier error 1059 on the generated `curriculum_course_mapping_id` FK name. The migration now uses explicit short FK names and is restart-safe for the known partial-DDL path by checking existing columns, foreign keys and indexes before adding them. Database impact and recovery semantics are recorded in `DB_IMPACT_ENR4_4.md`. Project-wide DB documentation completeness is governed by ADR 205.

## ENR-4.5 — Import validation contract / Admission Form required-field parity (2026-09-17)
Owner clarification locks the validation boundary before Confirm Import.

- The selected Session + Programme Offering remain the batch context. Curriculum academic rules are authoritative; CSV values never create or extend Discipline, Specialization, Course Category, Course, or Curriculum masters.
- Every import target marked required must be mapped before Preview. This includes Core required fields, curriculum-required academic choice targets, and applicable Admission Form fields whose Data Usage is `STUDENT_PROFILE` and `is_required = true`.
- Required `STUDENT_PROFILE` fields are validated again per CSV row. A mapped column with a blank value makes that row invalid and therefore blocks the all-or-nothing import.
- `APPLICATION_ONLY` fields are never import requirements because CSV migration does not create an Application.
- Specialization remains conditional: the shared `ApplicantAcademicPreferenceService` requires it only where the selected Discipline's configured curriculum path requires a Specialization.
- Discipline/Specialization/category-choice values must resolve against the selected Programme Offering's configured Curriculum. Missing or stale values fail validation; import never auto-creates academic masters.
- Mandatory curriculum papers continue to resolve automatically through the shared Admission academic preference service. Curriculum choice categories are required only where the shared curriculum resolver requires a student choice.
- If no applicable ACTIVE Admission Form Template mapping exists, Student Import is still allowed. The UI must explicitly disclose that only Core Student + Curriculum Academic Context are being collected; there are no template-driven Student Profile requirements in that case.
- If an applicable template exists but contains zero applicable `STUDENT_PROFILE` fields, that is a valid configuration, not an error.
- Confirm Import remains blocked whenever any row is invalid. Preview is not a bypass: the full CSV is revalidated server-side again inside the import boundary.

This is intentionally dynamic. A later Admission Form change that marks an applicable `STUDENT_PROFILE` field required is picked up by Student Import without hard-coding that field in the importer.

Database classification: **no schema migration / no new table / no column change**. ENR-4.5 tightens validation and consumes existing Admission Form metadata, Curriculum metadata, Student/Enrollment/Profile persistence, and the ENR-4.4 enrollment academic-context schema.

## ENR-4.6 — Admission / Import Academic Choice Parity (2026-09-17)

Student Import is an alternate entry route, never an alternate academic model. Academic-choice mapping now consumes the same `ApplicantAcademicPreferenceService` curriculum semantics as the Admission Form.

- **Academic package mode:** when one configured Offered From Discipline or Common / Interdisciplinary option completely satisfies a category, Import exposes one package mapping target. The selected package resolves all linked curriculum papers internally, exactly as Admission does.
- **Genuine course-choice mode:** when the Admission Form exposes real course-level choices, Import exposes one mapping socket per curriculum slot choice position. `min_selection` controls how many sockets are required; `max_selection` controls the total sockets shown. Therefore min=max 2 produces Choice 1 + Choice 2; min=max 3 produces Choice 1 + Choice 2 + Choice 3 without hard-coding MINOR or any category name.
- CSV values for genuine choices are actual Curriculum Course Codes. `Offered From`, Common / Interdisciplinary, category, term/semester placement, and mapping IDs are derived from the authoritative Curriculum Course Mapping; they are not independent CSV student data.
- Duplicate course choices, unavailable/wrong-category course codes, missing required choices, excess choices, and stale curriculum mappings are rejected. No academic master is auto-created by import.
- Saved mappings are schema-sensitive. A mapping saved before a curriculum choice cardinality/configuration change may become incomplete; the Required Mapping Check must force the newly required sockets to be mapped before preview.

**DB impact:** no schema change and no migration. ENR-4.6 changes import schema generation/resolution only and writes through the existing `student_enrollment_course_choices` structure created for ENR-4 academic context.

**QA status:** IN PROGRESS. Required QA: package parity, 1/2/3-choice cardinality, valid course codes, missing choice, duplicate choice, wrong-category/unavailable choice, discipline applicability, saved-mapping staleness, preview, transactional confirm import, and persisted enrollment course-choice parity.

## ENR-4.8 — Offered-From Selection Parity (2026-09-17)
Owner correction supersedes ENR-4.6's user-facing course-code rule for non-package applicant choices.

- Admission Form and Student Import must present the same applicant-facing academic choice: **Offered From** (Discipline name/code or Common / Interdisciplinary), not the internal Curriculum Course Code.
- The selected Offered From option resolves through the authoritative `curriculum_course_mappings` configuration to the actual mapping/course ID that is persisted. Offered From is presentation/input semantics; the resolved mapping remains the stored academic fact.
- For a non-package choice slot, each applicant-selectable Offered From option must deterministically resolve to exactly one active Curriculum Course Mapping. If one Offered From has multiple active mappings in the same selectable slot, the configuration is ambiguous and must be corrected rather than guessed.
- Slot `min_selection` / `max_selection` continue to control applicant choice cardinality. Min/max 2 means two Offered From selections; 3 means three, with duplicate resolved mappings rejected by the shared resolver.
- Existing Academic Package behavior is preserved: one Offered From/Common package can resolve multiple linked papers across the category only when `ApplicantAcademicPreferenceService::sourcePackageCandidates()` proves that the package completely satisfies the configured slots.
- Admission and Import both continue to persist actual Curriculum Course Mapping IDs and therefore converge on the same Enrollment course-choice structure.
- Import CSV accepts Offered From code/name for these choice sockets (for example `HISTORY`, `HINDI`, `COMMON`), never a raw internal mapping ID and no longer requires users to know course codes.
- No parallel academic resolver or Offered-From persistence column is introduced. `ApplicantAcademicPreferenceService` remains the shared authority.

**DB classification:** NO SCHEMA CHANGE / NO MIGRATION. This is a presentation + shared-resolution correction over existing Curriculum and Enrollment structures.

**QA status:** OWNER QA IN PROGRESS. Admission and Import must be tested on the same Curriculum and must resolve the same `curriculum_course_mapping_id` values before ENR-4 closes.

## ENR-4.9 Candidate/Application Offered-From parity (2026-09-17)
The internal College Applications / Candidate Eligibility create/edit form is a consumer of the same academic-preference contract as the public Applicant Admission Form and Student Import. It presents applicant-selectable CHOICE categories by **Offered From** option, not curriculum course code/name, while continuing to submit/save the resolved `curriculum_course_mapping_id` internally. Existing stored application course-choice rows remain authoritative; no data migration is required. Package-mode behavior remains unchanged. Ambiguous Offered-From configurations are blocked rather than guessed.

Permanent parity gate: future curriculum academic-choice changes must be checked across Public Applicant Admission Form, College Applications/Candidate Eligibility create/edit/re-entry, and Student Import. These are different entry surfaces over one curriculum contract, not separate academic models.
