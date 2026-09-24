# Attendance Operations

Status: IMPLEMENTED — OWNER QA REQUIRED

Routes:
- `/college/{college}/attendance` — eligible dated Class Schedule register.
- `/college/{college}/attendance/classes/{classSchedule}` — roster entry, policy context and finalized course percentage.

Permissions:
- `college_attendance.view`
- `college_attendance.manage`
- `college_attendance.finalize`
- `college_attendance.correct`

Scope and behavior:
- College scope is enforced by the complete Class Schedule → Timetable → Faculty Allocation → Course Offering → Batch → Programme Offering chain.
- The roster contains ENROLLED canonical Student Enrollments in the exact Batch, optional Section and exact Curriculum Course Mapping.
- Cancelled classes cannot receive attendance.
- Draft saving requires the complete current roster; finalization locks records and completes the class.
- Correction requires a meaningful reason and is audited.
- Empty roster, validation, processing and permission-aware action states are presented with shared themed components.
- No realtime transport is used; this is transactional Inertia/REST behavior.

Downstream status (2026-09-24): controlled Condonation, Medical/Special Exemption and final Examination Attendance Eligibility are implemented under ADR 214 and their dedicated PAGE_SPECs. They consume finalized raw attendance without mutating it.
