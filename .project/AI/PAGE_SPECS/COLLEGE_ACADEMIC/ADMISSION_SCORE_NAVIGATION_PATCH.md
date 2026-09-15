# Admission Score Capture Navigation Patch

- Score Capture / Normalization remains routed at `/college/{college}/admission-scores`.
- Its sidebar link must perform the actual Inertia GET on click without relying on prefetch.
- New admission-processing pages must never appear clickable while silently swallowing a failed prefetch.
- If a page has a new backend route/page component, navigation QA must verify:
  1. sidebar click changes the route,
  2. route resolves,
  3. Inertia page component loads,
  4. backend errors are visible rather than appearing as a no-op.
