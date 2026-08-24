# 006 — Hierarchical Sidebar Navigation

## Status
ACCEPTED — 2026-08-22

## Context
The ERP sidebar had grown as a flat list where University Profile, Colleges, Signatories, security pages, academic masters, and future operational modules all appeared at the same level.

That approach does not scale with the frozen ERP development hierarchy.

## Decision
Use a permission-aware collapsible tree sidebar aligned only to the implemented business/development hierarchy.

Current baseline:
- Dashboard
- Institution Setup
- Access Management
- Academic Setup
  - Academic Sessions
  - Degree Structure
  - Program Setup
  - Course Setup
- College Management only when College scope applies

The route layer remains independent from the visual grouping. Existing URLs such as `/admin/degrees`, `/admin/program-templates`, and `/admin/courses` remain unchanged.

## Tree and Accordion Rules
- Child items render with visible tree connector lines so parent/child relationships are clear.
- At each tree depth, only one sibling branch may be open at a time.
- Opening another root branch closes the previously open root branch.
- Inside Academic Setup, opening Degree Structure, Program Setup, or Course Setup closes the other branch at that same level.
- Clicking an already-open branch may collapse it.
- The branch containing the current route opens automatically after navigation or refresh.
- Active leaf styling remains distinct from expanded-parent styling.

## Motion Rules
- Expand/collapse uses short, subtle height/opacity motion and chevron rotation.
- Motion must feel responsive and must never delay navigation or actions.
- Reduced-motion preferences must disable non-essential transitions.
- Do not introduce an animation library only for sidebar motion; use the existing React/Tailwind design system unless a future architecture decision approves otherwise.

## Theme Rules
- Sidebar visuals must use semantic theme tokens/classes only.
- Do not hard-code theme-specific hex/RGB colors in the sidebar.
- Tree lines, active states, expanded states, text, focus states, and hover states must work with Premium Light, Premium Dark, Ocean Blue, Emerald, and future validated custom themes.
- Use the existing sidebar tokens such as sidebar foreground, accent, border, focus/ring behavior, and shared radius/transition standards.

## Permission Rules
- Leaf items declare their existing permission code.
- A leaf is omitted when the user lacks that permission.
- A branch is omitted when all of its children are omitted.
- College Management is omitted when there is no College scope.
- Backend controllers/middleware remain the actual security boundary.

## Scope Rule
This navigation decision does not authorize new ERP modules. Only modules already implemented and supported by the frozen hierarchy/documentation belong in the sidebar. Future items are added only when their approved milestone is implemented.

## Consequences
- Sidebar length remains manageable as the ERP expands.
- Navigation visually communicates the domain tree.
- One-open-at-a-time behavior reduces clutter.
- Motion improves perceived polish without violating the project's restrained interaction standard.
- No database or Laravel route migration is required.

## Curriculum Navigation Extension — 2026-08-22
Curriculum is a branch under Academic Setup, not a leaf route.

Current structure:
- Academic Setup
  - Curriculum
    - Curriculum Header

Only implemented Curriculum children are shown. Terms/Semesters and later Curriculum
structure pages must not appear until their milestone is implemented.


## Academic Policies Navigation Extension — 2026-08-24
Academic Policies Phase 1 has reached implementation, so the authenticated sidebar now exposes it under `Academic Setup` immediately after `Curriculum`.

Current implemented Academic Setup tail:
- Curriculum
- Academic Policies (`/admin/academic-policies`, permission `academic_policy.view`)

Academic Calendar remains absent until its own implementation milestone begins. This preserves the rule that the sidebar must not advertise unimplemented modules.
