# Interview Scheduling / Evaluation

## Position in hierarchy
Applications / Candidate Eligibility -> Score Capture / Normalization -> **Interview Scheduling / Evaluation when required** -> Merit / Roster Generation.

## Candidate visibility rules
- show only SUBMITTED + ELIGIBLE Application Choices with Score Capture present and locked Selection Rule Interview weight > 0
- never resolve a newer Selection Rule; use the exact rule locked on the submitted Application Choice
- reusable-panel candidate picker additionally excludes choices that already have an Interview

## Reusable panel scheduling
- College operator creates one Interview Panel and adds one or more eligible College Staff evaluators
- panel stores name, start date/time, candidate slot duration, finite working duration and optional venue/mode
- Interview date/time uses the ERP shared project-style date selector plus explicit hour/minute/AM-PM controls; browser-native `datetime-local` is not the Interview scheduling UI
- break start/end times use the same project-style explicit time selector pattern
- operator can add zero or more labelled breaks such as lunch or tea
- evaluator and candidate pickers are search/add controls; all available users/candidates are not pre-rendered as checkbox lists
- operator adds one or many interview-ready candidates
- system creates one separate candidate Interview row per selected Application Choice
- every generated slot must fit completely inside the panel working window
- a generated slot must never overlap a configured break; generation resumes from the break end
- if all selected candidates do not fit after break exclusion, panel creation is rejected atomically and no partial schedule is created
- panel working window is same-day; duration cannot carry scheduling into the next calendar day
- existing legacy candidate Interviews and previously-created panels remain valid

### Capacity example
Panel starts 10:00, working duration is 3 hours, candidate slot duration is 15 minutes, and lunch break is 11:00-11:30. Slots may be generated from 10:00 up to the 13:00 panel end, but no slot may overlap 11:00-11:30. Break time therefore reduces available candidate capacity.

## Individual / offline interview scheduling
- the candidate-level `Schedule Individually` action remains available for an interview-required candidate even when no reusable panel exists
- use it for one-off, offline, walk-in or manually arranged interviews
- operator captures panel/interviewer name, candidate schedule, venue/mode, evaluator(s), status and later raw/max scores
- an individually scheduled Interview is not forced into a reusable panel
- if the interview is saved directly as COMPLETED after an offline interview, normal evaluation/normalization rules apply

## Evaluator rules
- evaluator must be `COLLEGE_STAFF`
- evaluator must belong to the same College
- user and Role must be ACTIVE
- user must have a currently effective ACTIVE College-scoped role assignment
- that active College role must grant `college_admission_interview.evaluate`; this permission is configured through Role Permission Management
- `COLLEGE_ADMIN` is an explicit evaluator-eligibility exception and remains available when its College-scoped assignment is active
- assigning `college_admission_interview.evaluate` to a role such as Faculty makes active College Staff users with that role eligible for evaluator selection in that College; it does not make Applicant/Student identities eligible
- Applicant and Student identities are never valid evaluators
- panel-linked Interviews inherit the panel evaluator roster and cannot change that roster candidate-by-candidate
- store evaluator user reference plus name snapshot

## Evaluation rules
- COMPLETED requires raw + maximum score for every evaluator
- backend normalizes every evaluator score with `(raw / maximum) * 100`
- final normalized Interview component is the arithmetic mean of evaluator normalized scores
- on completion, write the Interview normalized score into the existing `college_admission_scores` row and re-run locked-rule qualification/final-weighted-score evaluation
- SCHEDULED/CANCELLED Interview has no Interview normalized score and Score remains PENDING_INTERVIEW unless another component already failed
- downstream Merit / Seat / Admission consumption locks Interview editing

## Candidate notification rules
- first scheduling sends Interview Scheduled email
- schedule change sends Interview Rescheduled email
- cancellation sends Interview Cancelled email
- reusable-panel scheduling sends a separate email to every assigned candidate with that candidate's generated slot
- SMTP credentials/configuration are central Laravel mail configuration (`MAIL_*` values in environment), never stored in Interview records or source code
- email failure is logged and does not roll back the saved academic schedule

## Audit
- candidate Interview create/update operations are audited
- reusable panel creation records assigned choice/evaluator IDs and configured breaks
