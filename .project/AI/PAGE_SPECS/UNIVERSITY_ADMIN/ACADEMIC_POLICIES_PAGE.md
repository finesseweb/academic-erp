# Academic Policies — Version Lineage Display

Academic Policy version display follows Curriculum.

- Current approved amendment: show version, `Current`, then `Amendment of v<parent version>`.
- Superseded older version: show version, then `Previous`.
- Independent clone/new policy: no amendment lineage text unless it was actually created as an amendment.

Example:

`v1.1`
`Current`
`Amendment of v1.0`

`v1.0`
`Previous`


## Academic Policy Scope Enhancement — 2026-08-25
The Academic Policy header Scope selector supports:
1. University-wide
2. Degree Level
3. Program Template
4. Curriculum

When `Degree Level` is selected, show a required Degree Level dropdown populated from active same-University `degree_levels`. The selected row is stored by `degree_level_id`, not as free text.

Scope UI rules:
- University-wide: hide/clear Degree Level, Program Template and Curriculum references.
- Degree Level: require Degree Level; hide/clear Program Template and Curriculum references.
- Program Template: require Program Template; Degree Level and Curriculum references remain blank.
- Curriculum: require current ACTIVE + APPROVED Curriculum; Degree Level remains blank. Program Template may be used as the UI filter and must match the selected Curriculum when supplied.

The list view displays the selected Degree Level name for `DEGREE_LEVEL` policies. Clone and Amendment keep the source policy scope and Degree Level reference.

## Current Session Default — 2026-08-25
For `Add Academic Policy`, the University's `ACTIVE + is_current` Academic Session is preselected when available. Other ACTIVE sessions remain selectable.
Editing/version history preserves the Policy's stored Academic Session and does not follow later Current-session changes automatically.
