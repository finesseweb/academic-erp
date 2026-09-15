# ADR 114 — Fee Demand UI must use the existing project page/layout pattern

## Decision
Applicable Fee Demands must render inside the layout already supplied by the ERP/Inertia shell. The page must not instantiate a second `AppLayout`.

The Fee Demand page follows the same page composition already used by College Fee Management and College Admission pages:
- page root fragment + `Head`;
- `space-y-6 p-4 md:p-6` content wrapper;
- standard `border-b pb-5` page header;
- College code/domain eyebrow, `text-3xl font-semibold` title and existing muted description treatment;
- existing shared Card, Button, Badge and Input components/classes only.

No inline `<style>`, CSS module, page-specific stylesheet, alternate shell, or independent visual system is introduced.

## Reason
The application shell already wraps College pages. Nesting `AppLayout` inside Fee Demands produced the visible double shell/header/container. Domain pages must remain visually and structurally consistent with the existing ERP implementation.
