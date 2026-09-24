# ADR 215 — Internal Assessment Setup, Assignment and Quiz

Status: IMPLEMENTED — OWNER QA REQUIRED

Internal Assessment uses the existing delivery hierarchy: ACTIVE Course Offering → resolved ACTIVE/APPROVED Academic Policy → configurable Assessment Component → type-specific Activity → ACTIVE Faculty Allocation → publication-time canonical Student Enrollment roster snapshot.

Components own type, maximum marks, weightage, optional pass marks and display order. Combined DRAFT/ACTIVE component weightage cannot exceed 100% per Course Offering. Components start DRAFT, become ACTIVE, and may become INACTIVE only when no open activity depends on them.

Assignment and Quiz activities inherit marks from their component. Both have governed open/close dates inside the exact Curriculum Term's active Academic Calendar period. Quiz additionally requires duration. They follow DRAFT → PUBLISHED → CLOSED. Publication revalidates the complete active hierarchy and transactionally snapshots exact enrolled students by Programme Offering, Batch, optional Faculty Allocation Section, and exact Curriculum Course Mapping choice. Later Marks Entry consumes this snapshot and must not reconstruct a mutable roster.

All College scope and permissions are enforced by Laravel. Mutations are audited. REST/Inertia is sufficient; no realtime transport is introduced.
