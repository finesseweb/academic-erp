# Selection Rule Responsive Dialog UI Fix — 2026-08-26

- Corrected Merit / Roster / Selection Rule create/edit dialog width so the shared `sm:max-w-lg` Dialog default cannot squeeze the form.
- Added viewport-safe dialog width, bounded height and scrollable form body.
- Kept dialog header/footer visible while long fields scroll.
- Added `min-w-0` / full-width protection to form fields and viewport constraints to Select dropdowns.
- Responsive two-column fields now collapse safely where required.
- Added canonical responsive dialog/form rules to `UI_UX_GUIDELINES.md` so future modules do not repeat this overflow issue.
- Added the Merit / Roster / Selection Rules PAGE_SPEC with its UI and optional-Reservation behavior.

No database or hierarchy change in this patch.
