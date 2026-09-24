# ADR 210 — Faculty Allocation Delivery Scope

Status: ACCEPTED / IMPLEMENTED — OWNER QA REQUIRED
Date: 2026-09-21

Faculty Allocation consumes an existing active Course Offering and references an active College Staff user whose effective College-scoped role grants `college_faculty_allocation.eligible`. It does not create a duplicate faculty/person master.

An allocation applies either to the entire Course Offering Batch or one existing Section of that Batch. It records teaching role (`PRIMARY`, `CO_FACULTY`, or `PRACTICAL`), optional weekly load and notes. New allocations start INACTIVE; active rows must be deactivated before editing.

The write contract requires an explicit `delivery_scope`: `BATCH` persists `section_id = NULL`; `SECTION` requires a selected ACTIVE Section belonging to the Course Offering Batch. The UI never substitutes the first available Section and clears stale Section selection when a parent changes.

Activation requires the College, Program Offering, Batch and Course Offering to remain ACTIVE. Section-scoped activation also requires the exact Section to be ACTIVE. Timetable must consume active Faculty Allocations rather than attach faculty directly to Course Master or Curriculum mappings.

Timetable periods, rooms, class meetings and attendance are outside this milestone.
