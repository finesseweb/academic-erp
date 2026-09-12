# Current Implementation State - Admission Setup Navigation / RBAC Patch

College Academic Setup now keeps Program Offerings, Intake / Seat Capacity, and Reservation / Seat Distribution at the academic setup level, while the downstream admission workflow is nested under **Admission Setup**.

College Role Permission Management dynamically groups Selection Rule, Admission Cycle, Admission Form, Application, Score, Interview, and Merit permissions under **Admission Setup** after migration.

No permission code or authorization semantics were changed.
