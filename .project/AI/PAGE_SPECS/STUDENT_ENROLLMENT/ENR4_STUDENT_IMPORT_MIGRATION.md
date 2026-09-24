# ENR-4 — Student Import / Migration

Status: IMPLEMENTED / OWNER QA REQUIRED

Route: `/college/{college}/student-imports`
Sidebar: Student Management → Student Import / Migration

## UI
1. Download CSV Template.
2. Upload CSV (max 5 MB).
3. Select Session → Programme Offering.
4. Map CSV headers to Full Name (required), DOB, Email, Phone, existing Student UID, University Roll, Class Roll and Discipline Code.
5. Validate & Preview. All rows are server validated; UI previews first 20 and shows total/valid/invalid counts.
6. Import only when invalid count is zero, through shared App Dialog confirmation, shared loading and Toast.

## Mutation contract
Creates Student (`source_type=IMPORT`) + Enrollment (`source_type=IMPORT`, `status=ENROLLED`) only. No Admission, Application, Applicant Profile, Fee Demand or Fee Clearance record is fabricated. Blank identity values remain Pending. Existing identity values are preserved after uniqueness/scope validation.

## QA gate
Owner QA must cover RBAC, College isolation, arbitrary-header mapping, invalid-row blocking/no partial mutation, successful import, Discipline-scoped identity compatibility, existing identity preservation, blank identity Pending behavior, Audit Log and Admission-origin enrollment regression.

### ENR-4.2 Dynamic Student Profile mapping
After Session + Programme Offering selection, mapping targets include core Student/Identity/Academic fields plus applicable Admission Form fields marked `STUDENT_PROFILE`. Academic field scopes are respected, so Programme Offerings can expose different profile targets. Mapping controls support CSV-column search and a CSV column may be mapped only once. Imported dynamic values persist to `student_profile_values`. `APPLICATION_ONLY` and file/image fields are excluded from CSV profile import.

### ENR-4.3 Drag-and-Plug mapper + reusable templates
The mapping workspace uses two visual sides: uploaded CSV Columns and authoritative Student Fields. Users drag a CSV plug onto a Student-field socket; connected headers are visibly locked and cannot be connected twice. No per-target search box/dropdown is required. Targets remain grouped as Core Student, Student Identity, Academic Context and Programme-aware Student Profile fields.

Mappings can be named and saved under the selected Programme Offering. Saved mappings are reusable across later upload batches and may have multiple named variants. Loading a mapping reconnects only columns that exist in the current CSV. Each saved mapping has a Download Template action that returns a ready-to-fill CSV containing that mapping's header row.

## ENR-4.4 Academic Context + Accordion Mapping
- Mapping groups are accordions. Core Student is open by default; Student Identity, Academic Context and Student Profile are collapsed by default. Opening one closes the previous group.
- Academic Context is generated from the selected Programme Offering Curriculum, matching Admission Form behavior: Discipline, applicable Specialization and curriculum-defined Choice categories.
- Mandatory curriculum courses are not manually mapped; they are resolved automatically.
- Choice-category CSV values must match configured curriculum option codes/names or course codes as instructed by the generated field hint.
- Validation uses the same `ApplicantAcademicPreferenceService` resolver as Admission.
- Successful import persists the resolved context on Enrollment and its enrollment-course links.

## ENR-4.5 validation UX
- Mapping groups are accordion-based; Core Student opens by default and the other groups remain collapsed until opened.
- Required targets display `*`. The right-side Required Mapping Check lists every required target that is not connected. Validate & Preview is disabled until all required mappings are connected; backend performs the same check authoritatively.
- When an applicable Admission Form Template exists, show the count of applicable Student Profile fields and required Student Profile fields.
- When no applicable ACTIVE Admission Form Template exists, show a visible informational warning. Import remains available using Core Student + Curriculum Academic Context only.
- Row preview reports required Student Profile blanks and curriculum resolution failures as row validation errors. Any invalid row blocks Confirm Import.

## ENR-4.6 Academic Choice Mapping

Academic Context must mirror the Admission Form's curriculum behavior. Package categories expose one mapping socket and resolve linked papers internally. Genuine course-level categories expose dynamic `Category — Choice N` sockets from each curriculum slot's min/max selection rules. Required sockets are positions `1..min_selection`; optional sockets extend through `max_selection`. CSV entries use actual Curriculum Course Codes. Offered From/Common-Interdisciplinary and semester placement are derived from Curriculum mappings and are never separately authored by CSV import.

When curriculum configuration changes, previously saved import mappings are not silently trusted: newly required targets remain unmapped and Required Mapping Check blocks preview until corrected and, if desired, re-saved.

## ENR-4.8 Offered-From academic choice contract
For applicant-selectable non-package Curriculum choices, Import mapping sockets are generated from slot min/max but their CSV value is the configured **Offered From** code/name. Example: MINOR min=max 2 exposes `MINOR — Offered From Choice 1` and `MINOR — Offered From Choice 2`; values may be `HISTORY` and `HINDI`. The shared academic preference service resolves each option to the one authoritative Curriculum Course Mapping and persists that mapping/course internally.

The Import UI must not require users to know Curriculum Course Codes for these choices. Common / Interdisciplinary uses `COMMON` (or its displayed name) and resolves identically. If an Offered From option is ambiguous within a slot (more than one active mapping), validation blocks the row/configuration rather than guessing. Academic Package mode remains one package selection when the shared service proves the package is complete.

## Cross-surface academic parity — ENR-4.9
Student Import must be QA-compared with both the public Applicant Admission Form and internal Applications / Candidate Eligibility. All three surfaces expose the same Offered-From semantics and resolve to the same authoritative curriculum course mappings. Any academic-choice change requires parity review of all three surfaces.
