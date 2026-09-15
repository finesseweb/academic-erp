# ADR 044 — Applicant Academic Choices and Curriculum Inheritance

## Decision
Public admission collects only genuine applicant academic decisions. The approved Curriculum remains the authority for compulsory course structure.

- Program Offering is already fixed by the mapped public admission URL.
- Applicant selects Discipline where the Program Template permits multiple disciplines.
- Specialization is OPTIONAL by default. A Program Template Discipline may explicitly set `specialization_required = true` only when the academic structure requires it.
- Mandatory curriculum courses are not presented as semester-by-semester choices. They are resolved from the approved Curriculum and carried in the application academic snapshot for downstream Student course allotment.
- Only `CHOICE` curriculum slots are interactive in the public application.
- Public labels use Course Category, not internal Curriculum Slot names.
- A Course may define a `source_discipline_id`. Choice courses are grouped as "From <Discipline>" so a course owned by Political Science can be offered as an elective to an English discipline without changing its academic ownership.
- Application submission is NOT constrained by seat capacity. Intake, reservation, merit and seat allocation remain downstream Admission responsibilities.

## Downstream contract
After Admission Approval creates/enables the Student identity, Student semester course registration must resolve mandatory courses from the student's approved Curriculum + Discipline + optional Specialization, and carry forward applicant-selected choice mappings for their applicable terms. Future-term mandatory courses should be generated when that term becomes operational rather than creating all transactional registrations on admission day.
