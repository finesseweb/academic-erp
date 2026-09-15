# Current Implementation State Patch — Generic Parent/Child Conditions

Stage 1 Form Builder now allows any existing eligible non-file field in the same template hierarchy to be used as the parent/source of an answer-based condition.

University Base:
- all existing non-file fields across all steps/panels are available as condition sources.

College Extension:
- all inherited University Base non-file fields are available;
- all existing College Extension non-file fields across all steps/panels are available.

No field family is hard-coded. Caste/EWS is only one possible use case.

FILE and IMAGE fields cannot be condition sources.

No migration is required for this patch.
