# college_admission_scores

## Purpose
Stores normalized admission scoring for one submitted + eligible Application Choice against the exact locked Selection Rule version.

## Ownership / linkage
- belongs to `college_admission_applications`
- belongs one-to-one to `college_admission_application_choices`
- references the exact locked `college_admission_selection_rules` version
- Interview score is reserved for the later Interview Scheduling / Evaluation module; it is not manually entered on Score Capture.

## Score fields
- Merit raw score / maximum score / normalized 0-100 score
- Entrance raw score / maximum score / normalized 0-100 score
- Interview normalized 0-100 score (future Interview module writes this)
- Final weighted 0-100 score

Normalization formula: `(raw / maximum) * 100`. Backend calculation is authoritative.

## Qualification lifecycle
- `QUALIFIED`: all currently required score components are present and all configured component/final thresholds pass.
- `NOT_QUALIFIED`: one or more configured thresholds fail.
- `PENDING_INTERVIEW`: the locked rule gives Interview a positive weight and the Interview module has not supplied its normalized score yet.

Only Selection Rule components with positive weight are captured here. Merit/Entrance thresholds and final weighted threshold come from the locked rule version.

## Integrity
- one score row per Application Choice
- scores can be captured only for SUBMITTED + ELIGIBLE choices
- score row always references the same Selection Rule version locked on the Application Choice
- downstream Merit/Seat/Admission processing must block score edits once consumed
