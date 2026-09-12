# ADR 037 — College Admission Form Setup Must Normalize Optional Nested Relations Before SSR

Date: 2026-08-31
Status: ACCEPTED

## Context
The College Admission Form Setup page reached React/Inertia SSR successfully but crashed with `Cannot read properties of undefined (reading 'map')`. The page renders templates with nested Eloquent relations including steps, panels, fields, field options, field conditions, field scopes, parent-template steps, and mappings. Older records or relation-loading differences can legitimately omit one of those arrays from the serialized payload.

## Decision
The College Admission Form Setup component must normalize every collection received from the server before rendering. Missing/null collections are treated as empty arrays. Nested templates are normalized recursively before they are passed to dialogs or rendered.

Normalized collections include:
- templates
- template.steps
- template.mappings
- parent.steps
- step.panels
- step.fields
- field.options
- field.conditions
- field.scopes
- users / academic selector lists / fee rules

Applicant registration settings also receive a safe runtime fallback to avoid an unrelated blank-screen regression if an older response omits the setting object.

## Consequences
- SSR no longer fails because a non-critical relation is absent.
- An older/incomplete template renders with the missing section empty rather than taking down the whole setup page.
- Backend relationships remain authoritative; normalization is render safety, not fabrication of business data.
- No schema change is required.
