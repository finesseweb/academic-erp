# Admission Score Capture SSR Import Fix

- `Spinner` must use the project's shared `@/components/ui/spinner` component.
- Do not import `Spinner` from `lucide-react`; that package does not export it.
- New React/Inertia pages must be checked for SSR-compatible imports before milestone delivery.
- Shared UI primitives should be imported from the existing project components instead of assuming an icon-library equivalent exists.
