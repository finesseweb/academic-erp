# ADR 128 — Scholarship Session Context Eligibility

## Decision
Scholarship / Concession / Waiver setup must not offer arbitrary Academic Sessions.

### University scope
- Show only University Academic Sessions that contain at least one **current ACTIVE + APPROVED Curriculum**.
- “Current Curriculum” follows the canonical Curriculum rule: ACTIVE + APPROVED and no approved successor/amendment.
- If the University Academic Session marked `is_current = true` is eligible, it is selected by default and sorted first.
- A specifically selected Program must have a current ACTIVE + APPROVED Curriculum in the selected Academic Session.

### College scope
- Show only Academic Sessions represented by an **ACTIVE College Program Offering** for that College.
- The Program Offering must itself reference a current ACTIVE + APPROVED Curriculum.
- If the eligible session marked `is_current = true` exists, it is selected by default and sorted first.
- Program Offering choices remain filtered by the selected Academic Session.

## Integrity
The same rules are enforced server-side. A stale/tampered request cannot save a benefit scheme against a session/offering that the UI would not present.

## Rationale
Scholarship policy is an academic-context-dependent financial rule. Showing sessions without an operative Curriculum/Program Offering creates unusable schemes and breaks downstream student applicability.
