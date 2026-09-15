# ADR 015 — Specialization Capacity Is an Optional Child, Not an Intake Mode

## Status
ACCEPTED

The earlier `ADMISSION_SPECIALIZATION` Intake mode and `is_admission_seat_bearing` concept are superseded for the current project model.

Specialization is an existing Program-mapped academic entity. When a College needs a capacity for it, the capacity is configured as an optional child of the parent Discipline capacity.

This does not make Specialization mandatory for all students.

General Discipline students remain supported through a nullable specialization allocation reference.

Do not reintroduce a mutually-exclusive Specialization Intake mode unless the master project architecture is explicitly changed by owner decision.
