# Decision 008 — Academic Policy Configure Menu

**Status:** Accepted  
**Date:** 2026-08-24

## Decision

Academic Policy rule sections are grouped under a single **Configure** action on the Academic Policies list.

Do not add a new table button for every future policy section.

## Reason

Academic Policy is intentionally extensible. Separate buttons for Credit / Completion, Attendance, Assessment / Examination, Grading, Promotion, etc. cause uncontrolled horizontal growth and make the list difficult to scan.

## Canonical pattern

```text
Policy row
└── Actions
    ├── Configure ▼
    │   ├── Credit / Completion
    │   ├── Attendance
    │   ├── Assessment / Examination
    │   ├── Grading
    │   └── future policy sections
    ├── Edit Header
    └── Validate Complete Policy
```

The Configure menu must indicate whether each section has already been configured.

## Boundary

`Configured` means the section's version-bound configuration record exists. It does not mean the complete Academic Policy has passed validation or approval.

Final validity is represented separately by the Academic Policy validation checkpoint and approval lifecycle.

## Progression configuration visual consistency

Promotion / Progression remains inside the Academic Policy Configure pattern. Its boolean regulation controls must reuse the same bordered explanatory-row visual language as other Academic Policy sections. Do not introduce a separate standalone design system for this page.
