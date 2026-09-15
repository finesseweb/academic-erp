# ADR 074 — Admission Form Runtime Validation Parity

## Status
Accepted — 2026-09-01

## Context
Admission Form Setup already stores intrinsic field rules (text mode, min/max/exact length, number range/precision, age rules), NUMBER/DATE cross-field comparisons, copy rules, and answer-based visibility. The backend dynamic-field validator is authoritative. However, the applicant-facing and college internal rendering did not surface every configured rule consistently at runtime: browser-native constraints covered only a subset, age validation was backend-only, cross-field validation was mainly checked during step navigation, and the college internal entry form omitted some native `required` attributes.

## Decision
Every effective Admission Form field rendered in either the public applicant form or the college internal application form must receive the same configured validation semantics that are stored on the template and enforced by the backend.

Runtime UI requirements:
- show a concise validation hint whenever a field has configured intrinsic or comparison rules;
- show an inline validation error as soon as a non-empty value violates a configured rule;
- enforce TEXT/TEXTAREA allowed-input modes and length rules;
- enforce NUMBER minimum, maximum, whole-number and decimal-place rules;
- evaluate DATE minimum/maximum age using completed calendar years and the configured TODAY/CUSTOM reference date;
- evaluate configured NUMBER/DATE cross-field comparisons against current in-form values;
- preserve required-when-visible behavior and never require a hidden conditional field;
- public NEW_WINDOW/step navigation must not unlock the next step while a currently visible field violates any configured rule;
- backend validation remains authoritative and must repeat all rules so client-side checks cannot be bypassed.

The public applicant form and college internal application entry must use a shared client validation helper to prevent future semantic drift.

## Implementation
Shared frontend helper:
`resources/js/lib/admission-field-validation.ts`

Runtime consumers:
- `resources/js/pages/public/admission-application.tsx`
- `resources/js/pages/college-admission-applications/index.tsx`

No database migration is required.
