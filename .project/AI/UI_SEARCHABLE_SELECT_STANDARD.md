# Searchable Select Standard

Status: ACTIVE PROJECT-WIDE UI CONTRACT
Date: 2026-09-21

Use `resources/js/components/ui/searchable-select.tsx` for single-select datasets that are dynamic, can grow, contain codes plus names, or commonly exceed eight options. Small fixed enums such as status, teaching role, yes/no, and a handful of immutable modes may continue using the standard Select.

## Required behavior
- Search matches the label plus supplied codes, email, role and contextual metadata.
- The current value remains visible while closed.
- Keyboard focus, Escape close, empty results, disabled state and bounded vertical scrolling are required.
- Forms submit the authoritative ID using the component `name` input.
- Changing a dependent parent clears every downstream selection.
- Preserve authoritative domain ordering before rendering options. Curriculum Terms/Semesters must sort by numeric `sequence_no` ascending; names and record creation order must never override that sequence.
- Current Academic Session is the default when `is_current` is available.
- Default academic order is Session → Program Offering → Discipline → Semester/Term → operational record.
- Never accept free-text IDs or create duplicate masters from selector text.

## Scalability
- Client-side search is suitable for already-loaded, College-scoped lists of practical size.
- Above approximately 100 options, or when payload size becomes material, use debounced server-side search with limited/paginated results while retaining this interaction contract.
- New pages and materially touched pages must follow this standard. Existing local searchable picker copies should migrate to the shared component when those pages are next changed.
