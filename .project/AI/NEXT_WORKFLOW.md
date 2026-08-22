# NEXT WORKFLOW — AUTOMATIC MILESTONE EXECUTION

> **Highest-priority command interpretation:** `next N` / `nextN` is one owner command approving N sequential eligible incomplete milestones. It MUST NOT be downgraded to a single milestone because of generic approval-gate, one-page, one-milestone, or stop-after-work wording elsewhere. Plain `next` alone means N=1.

## Owner Commands
The project owner may say:

- `next` -> approve and implement 1 next eligible incomplete milestone.
- `next 2` or `next2` -> approve and implement 2 next eligible incomplete milestones.
- `next 3` or `next3` -> approve and implement 3 next eligible incomplete milestones.
- `next N` or `nextN` -> approve and implement exactly N next eligible incomplete milestones, where N is any positive integer.

Do not ask the owner to repeat milestone names when the frozen hierarchy and repository state make the sequence unambiguous.

## Continuation / Resume Rule — Mandatory
Every `next` command MUST continue from where the existing project actually stopped.

Before counting any requested milestone:
1. Inspect the current repository/code, routes, UI, APIs, migrations/schema, permissions, tests and working behavior.
2. Reconcile that reality with `CURRENT_IMPLEMENTATION_STATE.md`, `PAGE_IMPLEMENTATION_REGISTRY.md`, PAGE_SPEC statuses and the frozen hierarchy.
3. Treat repository reality as the source of truth when status documentation is stale.
4. Already-completed milestones are NEVER rebuilt merely because `next`/`next N` was issued.
5. Already-completed milestones do NOT consume the requested N count.
6. Resume at the earliest genuinely incomplete eligible milestone.
7. If that milestone is partially implemented, completing it counts as the first requested milestone.
8. Never reset to the beginning of the hierarchy unless the repository itself is actually empty/incomplete from there.

Replacing these workflow files in an existing project therefore changes command behavior only; it does not reset implementation progress.

## Mandatory Documentation Root
Always read from:

`.project/AI/`

Required before selecting work:
1. DOCUMENTATION_ROOT.md
2. PROJECT_CONSTITUTION.md
3. MASTER_DEVELOPMENT_HIERARCHY.md
4. NEXT_WORKFLOW.md
5. CURRENT_IMPLEMENTATION_STATE.md
6. PAGE_IMPLEMENTATION_REGISTRY.md
7. DOMAIN/HIERARCHY_TO_PAGE_MAP.md
8. relevant PAGE_SPEC(s)
9. relevant SECURITY / DATABASE / THEMING / REALTIME / ADR docs

## Algorithm on `next`, `next N`, or `nextN`

### 1. Parse Requested Count
- `next` means N = 1.
- `next N` and `nextN` mean N = the supplied positive integer.
- Do not interpret unrelated text beginning with "next" as a batch command unless it matches these forms.

### 2. Inspect Repository Reality
Inspect source code, routes, UI, API, Laravel migrations / Eloquent/schema, permissions, tests and working behavior.

Repository reality overrides stale documentation status.

### 3. Reconcile Status
If a milestone is already fully implemented but documented as NOT_STARTED/IN_PROGRESS, validate it and synchronize status. Do not count it toward N.
If documented as IMPLEMENTED but incomplete, correct it to the real state and treat it as incomplete.

### 4. Find the Current Position
Read `MASTER_DEVELOPMENT_HIERARCHY.md` from top to bottom and identify the earliest genuinely incomplete eligible milestone after reconciliation.

### 5. Select the Earliest Eligible Incomplete Milestone
A milestone is eligible only when all required prerequisites before it are fully implemented.

If the earliest eligible milestone is partially implemented, finish that milestone first. Do not skip it.

### 6. Read PAGE_SPEC
Read all PAGE_SPECs related to the selected milestone. PAGE_SPEC defines what to implement. The frozen hierarchy defines when to implement it.

### 7. If PAGE_SPEC Is Missing
Create the PAGE_SPEC for that selected milestone first from the frozen hierarchy and existing architecture. Then implement it in the same milestone iteration if no major unresolved product decision remains.

### 8. Implement One Milestone Completely
Implement the selected milestone completely and professionally. Include only minimum technical dependencies needed for it. Do not start the following milestone until this milestone is validated and documented.

### 9. Validate
Run appropriate:
- lint
- typecheck
- tests
- build
- Laravel migrations / Eloquent/schema/migration validation
- RBAC/security tests
- responsive/theme checks for UI work

### 10. Synchronize Documentation
Update:
- relevant PAGE_SPEC status
- CURRENT_IMPLEMENTATION_STATE.md
- PAGE_IMPLEMENTATION_REGISTRY.md
- affected architecture/database/security/domain docs

### 11. Count and Continue Within the Same Approved Batch
After a milestone is complete, validated and documented:
- increment the completed-batch counter by 1;
- if the counter is less than N, re-inspect/reconcile as needed and select the next earliest eligible incomplete milestone;
- then repeat Steps 5-10;
- never count a milestone that was already complete before this command.

### 12. Report and STOP
When exactly N requested milestones have been completed, report:
- requested command/count
- starting/resume position
- each milestone implemented in order
- validation performed
- documentation updated
- next eligible incomplete milestone

Then STOP. A new owner command is required for additional milestones beyond the requested N.

## `next` / `next N` Does NOT Mean
- implement all remaining modules without a count
- start again from milestone 1
- skip prerequisites
- rebuild already completed functionality
- count pre-existing completed milestones toward N
- create fake placeholder pages
- silently change architecture
- use any documentation root other than `.project/AI/`

## Major Architecture Conflict / Blocker
If a selected milestone requires a major unapproved architecture/product decision or cannot safely proceed:
- remain on that same milestone;
- explain the conflict/blocker;
- ask only for the required decision when truly necessary;
- do not skip ahead;
- stop the remaining batch at that point.

## Permanent Interpretation
`next` means:

"Resume from repository reality, then implement exactly the next 1 eligible incomplete milestone from the frozen hierarchy, validate it, update documentation, report, and stop."

`next N` or `nextN` means:

"Resume from repository reality, then sequentially implement exactly the next N eligible incomplete milestones from the frozen hierarchy. Complete, validate, and document each milestone before beginning the next. Already-completed milestones are skipped and do not consume N. Report and stop after N newly completed milestones."

## Laravel + React Structure Gate
Before implementing each selected milestone, read:
- `PROJECT_FOLDER_STRUCTURE.md`
- `MODULE_NAMING_STANDARD.md`
- `PAGE_FLOW_STANDARD.md`
- `REACT_FRONTEND_STANDARD.md`
- `LARAVEL_BACKEND_STANDARD.md`

Each milestone must follow the same React/Laravel folder and page-flow conventions as existing modules. Do not create a new custom folder pattern for each page.


## Curriculum Structure Update — 2026-08-22
- Curriculum Header: implemented.
- Terms / Semesters: implemented.
- Curriculum Slots Phase 1: implemented with Course Category, Slot Name and Display Order.
- Slot-level Credits are not introduced.
- Course / Paper Mapping remains a later milestone.
- Copy / Clone Structure is approved as the future reuse pattern when a new Curriculum/version needs an existing structure; source and target records remain independent.


## Current Curriculum Continuation — 2026-08-22
Curriculum Header -> Terms / Semesters -> Slots Phase 1 -> Slots Phase 2 are implemented.

Next eligible implementation:
`Course / Paper Mapping`

Do not insert Slot Credit before Course / Paper Mapping. Copy / Clone remains an approved future reuse capability and is implemented only when its milestone is reached.


## Curriculum Course / Paper Mapping Update — 2026-08-22
Course / Paper Mapping is implemented.

Next eligible implementation:
`Mapping Display Order`

Do not introduce Credit, credit totals or Structure Validation before their frozen milestones.


## Mapping Display Order Update — 2026-08-22
Mapping Display Order is implemented.

Do not introduce Semester Credit Totals or Curriculum Total Credits until the documented Credit definition stage exists.

Next implementation should be chosen from the remaining frozen Curriculum sequence without inventing Credit placement.


## Mapping Context Correction — 2026-08-22
Course Mapping is now aligned with the Program Template many-to-many Discipline/Specialization model. Continue only from the frozen documentation.


## Next after Validation Phase 1 — 2026-08-22
Implement Copy / Clone Structure using the validated Curriculum structure. Do not introduce Credit fields during Clone.


## Credit-before-Clone Order — 2026-08-22
User-approved implementation order:
`Slot Credit Binding -> Credit Summary -> Final Credit-aware Curriculum Validation -> Copy / Clone Structure`

Do not implement Clone before Credit Summary and final credit-aware validation are complete.


## After Credit Summary — 2026-08-22
Next implementation:
`Final Credit-aware Curriculum Validation`

After it passes:
`Copy / Clone Structure`


## Next Workflow — 2026-08-22
Credit Binding, Credit Summary and final Credit-aware Curriculum Validation are complete.

NEXT:
`Copy / Clone Structure`

Clone must include Terms, Slots, Slot Credits, selection rules, Course Mapping, Discipline/Specialization context and Mapping Display Order.


## Clone Structure Completion — 2026-08-22
Complete Curriculum Clone, Semester Clone and Slot Clone are implemented.

Do not add another clone layer unless a future documented workflow requires it.
Continue with the next frozen University Academic Setup milestone after Curriculum.


## Assignment Lock Rule — 2026-08-22
When Curriculum -> Student Group/Cohort Assignment is implemented, connect its canonical assignment table to `CurriculumStructureDeleteService::assertDraftAndUnassigned()` and hide destructive actions for assigned Curricula.


## Frozen Clone Hierarchy — 2026-08-22
Entire Structure = whole Curriculum.
Semester Clone = selected Semester + all descendants.
Slot Clone = selected Slot + all descendants.
Partial clone may target another compatible DRAFT Curriculum version.


## Delete Guard for Future Assignment Module — 2026-08-22
When Student Group/Cohort Curriculum Assignment is implemented, its canonical existence check must be connected to the centralized `CurriculumStructureDeleteService::assertDraftAndUnassigned()` before assignment goes live. This single guard protects Curriculum, Term, Slot and Mapping hard-delete operations.
