# ADR 049 — Applicant Specialization Visibility Requires Active Curriculum Usage

Date: 2026-08-31
Status: Accepted

## Decision

A Specialization being configured under a Program Template Discipline is not enough by itself to show the Specialization field in Public or Internal Admission Application entry.

For the selected Program Offering, a Specialization is applicant-relevant only when the offering's ACTIVE Curriculum contains at least one ACTIVE Curriculum Course Mapping for:

- the selected Discipline, and
- that Specialization, and
- an ACTIVE Curriculum Term / Slot / Course.

If no specialization-specific course mapping exists for the offering Curriculum, the Admission Application must hide the Specialization field completely and persist `specialization_id = NULL`.

If one or more effective specializations exist, only those effective specializations are exposed to the applicant/admin.

The Program Template `specialization_required` flag is enforced only when at least one effective specialization exists. This prevents an academically empty specialization selector from blocking an application.

## Example

BA -> English has template specializations English Literature and Linguistics.

If the active BA Curriculum has no paper mapped with `specialization_id = English Literature` or `Linguistics`, the Admission Form shows no Specialization field.

After `Victorian Literature` is mapped as `English + English Literature`, English Literature becomes an available specialization on the Admission Form. Linguistics remains hidden until at least one active curriculum paper is mapped to Linguistics.

## Boundary

Curriculum Course Mapping remains the authority for whether a specialization has curricular effect in a particular offering. Program Template remains the authority for what specializations may be configured and whether specialization is generally required when effective specializations exist.
