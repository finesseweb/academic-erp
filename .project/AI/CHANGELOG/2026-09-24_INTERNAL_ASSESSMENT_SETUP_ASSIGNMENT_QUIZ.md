# 2026-09-24 — Internal Assessment Setup, Assignment and Quiz

- Implemented the first three Phase 15 milestones.
- Added policy-linked configurable assessment components.
- Added governed Assignment and Quiz activity lifecycles.
- Added publication-time canonical Student roster snapshots for later Marks Entry.
- Added three College pages, four permissions, audit events, protected-role grants and cleanup dependencies.
- Corrected the three GET controller signatures to use the exact `{college}` route-parameter name required by Laravel implicit model binding; Setup, Assignments and Quizzes now resolve the persisted College instead of an empty model.
