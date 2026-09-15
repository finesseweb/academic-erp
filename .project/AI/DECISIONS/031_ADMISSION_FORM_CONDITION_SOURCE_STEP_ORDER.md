# ADR 031 — Admission Form Conditional Source Step Ordering

## Status
Accepted — Stage 1 QA correction.

## Decision
Answer-based conditions in Admission Form Setup must select their source from fields that are already available at runtime. The builder groups eligible source fields by Step.

For a University Base field, eligible sources are non-file fields in the same Step or an earlier Step of the same template. Later Steps are not eligible.

For a College Extension field, every inherited University Base non-file field is eligible because the University Base renders before College extension content. Within the College Extension, only fields in the same Step or an earlier College Step are eligible.

`FILE` and `IMAGE` fields cannot be condition sources.

The backend enforces the same ordering rule as the UI so a crafted request cannot create a forward/circular dependency.

## Rationale
A field cannot safely depend on a value that the applicant has not reached yet. Grouping by Step also makes the builder understandable when many fields exist.

## Result
The `Show only when field` dropdown displays eligible fields under Step labels rather than a flat list. If no source exists, the builder explains that a non-file field must first be added in the same or an earlier Step.
