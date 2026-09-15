# program_template_discipline_specializations — Admission Seat Flag

New column:
- `is_admission_seat_bearing` boolean, default `false`

Meaning:
- `false` = academic/optional specialization only; not eligible for Intake seat allocation.
- `true` = specialization is explicitly used as a sanctioned admission seat bucket for that Program Template + Discipline mapping.

Default must remain false so existing academic specializations are never silently converted into admission quotas.
