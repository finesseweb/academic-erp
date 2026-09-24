# Phase 13 — Course Offering Curriculum Credit Linkage Correction

Date: 2026-09-20

## Change
Corrected Course Delivery credit summaries so they are derived from the existing University Curriculum Slot contract rather than from the number of Course Offering rows.

## Rules preserved
- MANDATORY Slot: Required = Maximum = Slot Credits.
- CHOICE Slot: Required = Slot Credits × Minimum Selection; Maximum = Slot Credits × Maximum Selection.
- NON_COUNTABLE Slot: numeric credit remains visible but is excluded from countable totals.
- Slot is counted once even when several mapped/offered courses belong to that Slot.
- Course Offering does not persist or override Curriculum credit rules.

## Scope
UI/derived-summary correction only. No migration, schema, hierarchy, Curriculum, Enrollment, Batch, Program Offering, Discipline, or Specialization change.

## QA
Owner QA required. Verify a Choice Slot with multiple offered alternatives does not inflate Required Credits beyond the Curriculum `min_selection` rule, and that Maximum Credits follows `max_selection`.
