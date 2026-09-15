# Current Implementation State Patch — Applicant Premium Public Shell

- `applicant/*` Inertia pages are explicitly rendered without the internal ERP AppLayout.
- Applicant gateway now uses a dedicated public admission shell.
- Gateway shows College/University identity and mapped Program/Admission Cycle/Session context when available.
- New Applicant and Existing Applicant use a compact tabbed card instead of two competing full-width cards.
- UI uses existing theme tokens only and therefore follows ERP theme changes.
- Registration DOB continues using the shared ERP DatePicker.
- No database migration is required for this UI/layout patch.
