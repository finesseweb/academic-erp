# ADR 129 — Scholarship Action Controls Follow Project Icon Pattern

Date: 2026-09-07
Status: Accepted

## Decision
Scholarship / Concession / Waiver list actions must use the existing ERP action-button visual language already used by modules such as Reservation / Seat Distribution and Academic Calendar.

- Edit uses the shared outline small Button with the Lucide `Pencil` icon.
- Activate uses the shared outline small Button with the Lucide `Power` icon.
- Deactivate uses the shared outline small Button with the Lucide `PowerOff` icon.
- Text labels remain visible beside icons for clarity and accessibility.
- No page-specific CSS or new visual system is introduced.
- RBAC and lifecycle behavior are unchanged; this is a presentation-consistency correction only.

## Rule
New action controls in this module must reuse existing project components and icon conventions instead of text-only action buttons when the equivalent project action pattern already exists.
