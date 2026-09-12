# Current Implementation State Patch — Public Admission URL

Stage 1 now includes the public candidate entry layer for an operational College Form Mapping.

Current flow:
University Base Form → optional College Extension → College Program Offering + Admission Cycle Mapping → Public Enable → `/apply/{slug}` → public REGULAR Application submission → existing Application/Application Choice → Eligibility → Score → Interview → Merit.

Public availability is derived from the existing Admission Cycle application dates. No duplicate public-candidate transaction model was introduced. Direct Admission remains internal.

QA is still required. The frozen downstream hierarchy has not advanced.
