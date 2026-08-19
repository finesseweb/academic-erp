# IMPLEMENTATION APPROVAL POLICY

## Core Rule
The ERP may be fully documented in advance, but implementation is approval-gated.

A PAGE_SPEC existing in `.project/AI/` means:
- the requirement is planned/documented;
- Codex/AI may understand dependencies and design for compatibility;
- Codex/AI MUST NOT implement it automatically.

Implementation begins only when the project owner explicitly approves the page or milestone.

## Required Lifecycle
PLANNED / DOCUMENTED
→ OWNER APPROVES PAGE OR MILESTONE
→ IN_PROGRESS
→ IMPLEMENTED
→ PENDING_REVIEW
→ OWNER ACCEPTS
→ ACCEPTED

Never automatically start the next page after completing the approved page.

## PAGE_SPEC Status Block
Each page should use:

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE

When owner approves:
Implementation Approval: APPROVED
Implementation Status: IN_PROGRESS

After implementation:
Implementation Status: IMPLEMENTED
Review Status: PENDING_REVIEW

After owner acceptance:
Review Status: ACCEPTED

## Dependency Rule
An approved page may introduce only the minimum professional foundation/dependency required for that page.
Do not use a dependency as permission to implement unrelated future pages.

## Major Change Rule
If implementation requires a major architecture/product change not already approved:
1. stop;
2. explain the proposed change;
3. obtain owner approval;
4. update ADR/docs;
5. implement.

## Documentation Updates
Normal implementation changes: update affected docs automatically.
Major architecture/product changes: approval first, then update docs and code.

## `next` Is Explicit Milestone Approval

The owner command `next` approves exactly the earliest eligible incomplete milestone from the frozen master hierarchy.

It removes the need for the owner to manually repeat that milestone name.
It never approves all subsequent milestones.
