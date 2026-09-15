# ADR 047 — Test Data Cleanup Covers Applicant Academic Children

Date: 2026-08-31
Status: Accepted

Stage 1 Test Data Cleanup must remove all application-owned transactional child rows before deleting the application: dynamic field values, applicant curriculum course choices, academic preference header, and legacy program/seat choices. Full Academic Reset also removes college applicant registration settings. Applicant login users/profiles remain preserved in accordance with the existing reset policy that preserves users and login identities.

Curriculum cleanup treats `college_admission_application_academic_preferences.curriculum_id` as a downstream operational reference so a curriculum with active applicant preference data is blocked cleanly instead of failing later on a foreign key.

2026-09-01 cleanup reconciliation: Admission Form Setup now also owns cross-field comparison and copy-behaviour rule rows. `college_admission_form_field_comparisons.source_field_id` and `college_admission_form_field_copy_rules.source_field_id` / `trigger_field_id` intentionally use RESTRICT foreign keys so a referenced field cannot be removed accidentally during normal builder operations. Test Data Cleanup is different: when an authorised template cleanup is already allowed, it must first delete all comparison, copy-rule, and conditional rows that target or reference any field in that template, then allow the existing template -> step -> field cascades to run. This ordering applies both to single Admission Form Template cleanup and full Academic Reset. No schema change is required.

## 2026-09-01 QA reconciliation: legacy unlinked Regular applications

Regular public submissions created before the automatic Admission processing bridge may exist with `admission_mode = REGULAR`, `entry_source = PUBLIC`, `status = SUBMITTED`, but no row in `college_admission_application_choices`. These records are legacy QA submissions and cannot participate correctly in Eligibility -> Score -> Interview -> Merit/Roster processing.

Test Data Cleanup now identifies these records explicitly as **LEGACY UNLINKED REGULAR APPLICATION** and provides a guarded bulk cleanup action using confirmation code `CLEAN-UNLINKED-REGULAR-APPLICATIONS`.

Cleanup remains dependency-safe:

- only PUBLIC + REGULAR + SUBMITTED applications without a processing-choice link are candidates;
- application dynamic field values, academic preferences, curriculum course choices and any application-choice children are deleted child-first;
- records with downstream Score, Interview, Merit, Seat Allocation, Admission or Student references are preserved and reported as blocked;
- Applicant `users`, Applicant Profiles and issued Registration Numbers remain preserved according to the existing test-reset identity policy;
- foreign keys remain enabled and are not weakened to make cleanup easier.

This is a test-maintenance reconciliation path only. Production workflow must never rely on deleting an unlinked submission; new Regular public submissions must create the processing bridge during submission.

## 2026-09-01 — Legacy unlinked Regular cleanup audit scope fix

The bulk `Clean Legacy Unlinked` action is university-scoped. Its audit record must therefore use the current `university_id` as `resource_id`; it must not pass `null` into the shared `audit(..., int $id, ...)` contract. This preserves the existing non-null integer audit contract and makes the cleanup event traceable to the University whose QA applications were cleaned. No schema or migration change is required.


### Dynamic Selection Rule merit-source references
Selection Rule Merit Source rows can RESTRICT-delete Admission Form fields through their obtained/maximum field foreign keys. Test cleanup therefore removes these reference rows before deleting Admission Form field trees, and individual Selection Rule cleanup reports/deletes its Merit Source children together with tie-breakers.
