# Current Implementation State Patch — Interview Panel Capacity / Breaks

Interview reusable-panel scheduling is now capacity-bound by a same-day working duration. Multiple breaks can be configured and are excluded from slot generation. Bulk candidate/evaluator selection uses search-and-add controls. Panel creation fails atomically when selected candidates cannot fit. Candidate-level individual/offline scheduling remains available and continues to use the same locked Selection Rule, evaluator integrity and score normalization rules.

Schema addition: nullable `college_admission_interview_panels.session_duration_minutes` plus `college_admission_interview_panel_breaks`. Existing panel/interview history is preserved.
