# Changelog Patch — 2026-08-26 — Reusable Course Mapping by Discipline

- Corrected Curriculum Course / Paper Mapping so Course Master records remain reusable across eligible Disciplines/Specializations in the same Slot.
- Replaced the legacy Slot + Course uniqueness rule with contextual uniqueness at Slot + Discipline + Specialization + Course.
- Updated available-course loading so a Course mapped to English is not globally removed from Sociology/Hindi/etc. contexts.
- Updated backend duplicate validation to reject only the exact same contextual mapping.
- Updated the mapping modal to clear stale Course selection when Discipline/Specialization changes and to show a context-specific exhausted-options message.
