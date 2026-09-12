# ADR 036 — College Admission Form Setup Must Receive Current Curriculum Context

## Status
ACCEPTED — 2026-08-31

## Context
The College Admission Form Setup field builder supports academic applicability by Degree Level, Degree, Program, Curriculum, Program Offering and Admission Cycle. The React page therefore requires the current Curriculum option list.

A runtime regression caused the College Admission Form Setup page to render blank after the backend request succeeded because the controller did not include the `curricula` prop expected by the page.

## Decision
- College Admission Form Setup must load Curriculum options using the same current-version contract as College Program Offering.
- Selectable Curriculum = `ACTIVE` + `APPROVED` + no approved amendment/successor.
- Historical/superseded curricula remain stored but are not offered for new field applicability configuration.
- The College page receives `curricula` explicitly from the controller.
- Frontend collection props use empty-array fallbacks so absence of an optional/list prop cannot crash the whole page into a white screen.

## Consequences
- Admission Form Setup remains aligned with the existing Curriculum amendment model.
- Field applicability does not accidentally target superseded curriculum versions.
- Missing list data fails gracefully instead of causing a blank React page.
