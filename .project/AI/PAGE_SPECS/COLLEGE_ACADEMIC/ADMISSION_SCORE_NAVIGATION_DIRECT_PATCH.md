# Admission Score Navigation Direct Patch

- Score Capture / Normalization is a required stage between Eligibility and Merit/Roster generation.
- It records rule-required raw Merit/Entrance scores, normalizes them to 0-100, evaluates component thresholds, and prepares the weighted score.
- Interview scores are supplied later by Interview Scheduling/Evaluation when the locked Selection Rule requires Interview.
- The Score Capture sidebar entry uses direct browser navigation while this new module is being owner-QA tested, so route/controller errors cannot be hidden by client-side navigation behavior.
- After the module is accepted and navigation behavior is verified, it may be returned to the shared Inertia navigation pattern only if identical reliability is confirmed.
