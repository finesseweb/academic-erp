# ADR 033 — Public Application Step Layout

## Decision
The existing mapping-level `public_open_mode` is interpreted as the public application's **step layout**, not as the browser target of the public URL.

- `SAME_WINDOW`: render all configured template steps on one application page, preserving the existing continuous form behavior.
- `NEW_WINDOW`: render the public application as one screen at a time: Candidate/Seat Category → each configured Template Step in order → Review/Fee/Submit. Previous/Next navigation preserves entered values because all controls remain mounted inside the same form while inactive screens are hidden.

The public URL itself opens normally in the current tab unless the browser/user explicitly opens it elsewhere.

## Validation
In one-step-at-a-time mode, Next validates the currently visible screen before advancing. Final Laravel validation and all template conditional/applicability rules remain authoritative at submission.

## Rationale
A browser-tab-per-step design would split one application across independent documents and would make file inputs, validation state, and unsaved values unsafe. A wizard-style separate screen per step provides the intended visual separation without breaking application continuity.
