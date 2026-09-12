# ADR 088 — Merit / Roster Candidate Discipline Display

## Decision
Discipline is candidate/application academic-preference context, not a header attribute of a program-wide Merit / Roster rule group.

- Remove Discipline from the Merit rule/group header.
- Keep Degree Level and Degree in the rule/group academic context.
- Display each candidate's submitted academic-preference Discipline beside the candidate in Ranking Preview and Generated Roster.
- If the application also has a Specialization, display it beside that candidate after Discipline.
- Source candidate Discipline/Specialization from `CollegeAdmissionApplication::academicPreference`, preserving the applicant's admission context even when the Intake uses a PROGRAM seat bucket.
- Do not infer a candidate discipline from the Selection Rule bucket.
- Ranking, qualification, tie-breakers, generation locking and reservation behavior are unchanged.
