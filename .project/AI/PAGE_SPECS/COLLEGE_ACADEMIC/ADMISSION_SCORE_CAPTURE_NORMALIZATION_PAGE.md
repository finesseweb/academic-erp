# Score Capture / Normalization

## Position in hierarchy
Applications / Candidate Eligibility -> **Score Capture / Normalization** -> Interview Scheduling / Evaluation when required -> Merit / Roster Generation.

## Page rules
- list only SUBMITTED applications whose individual choice is ELIGIBLE
- show Program Offering, preference/seat bucket and exact locked Selection Rule version
- searchable by application number/candidate/email/phone
- capture Merit only when Merit weight > 0
- capture Entrance only when Entrance weight > 0
- Interview is displayed as downstream/pending and is never manually entered here
- raw + maximum score are entered; UI may preview normalization but backend calculation is authoritative
- show component minimum thresholds from the locked rule
- show final qualification state and final weighted score when calculable
- use project-standard modal sizing, responsive fields and visible backend errors

## Current test example
For `UG-MERIT V1` with Merit 100%, Entrance 0%, Interview 0% and Minimum Merit 50, only Merit raw/max inputs appear. A normalized score >=50 becomes QUALIFIED.
