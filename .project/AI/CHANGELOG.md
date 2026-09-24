# ENR-3 corrective patch — 2026-09-17

- Added Student Management → Students to System Maintenance → Test Data Cleanup.
- Added targeted Student cleanup for ENR-2/ENR-3 QA data.
- Student cleanup removes Student Profile values, Student Enrollment and Student master only; source Application/Admission/Fee data remain intact.
- Self-registered Applicant linkage is safely restored to APPLICANT when its test Student is cleaned.
- Individual cleanup deliberately does not rewind `student_identity_sequences`.
- No migration added by this corrective patch.

## 2026-09-17 — ENR-3.3 Configurable Class Roll Scope
- Added College Student Identity setting: Class Roll Scope = Programme Offering or Discipline.
- Added scope-aware Class Roll generation and database uniqueness without renumbering existing students.
- Added migration `2026_09_17_110000_add_configurable_class_roll_scope`; no new domain table.
- Updated ADR 203, Student Identity page spec, DB table specs/catalog/relationship map, and implementation state.

## 2026-09-17 — ENR-3.4 Manual Identity Assignment + Identity-only Test Cleanup
- Removed automatic Student UID / University Roll / Class Roll assignment from the ENR-2 enrollment transaction. A newly enrolled Student now enters Student Identity as PENDING and identity is issued only through the authorized Student Identity Assign action.
- Added Test Data Cleanup > Student Management > Student Identity Assignments. This clears Student UID, University Roll and Class Roll only; Student, Enrollment, Profile, Application, Admission and Fee data remain intact.
- Identity sequence counters are never rewound by identity-only cleanup, so previously issued QA numbers remain non-reusable.
- No database migration and no new domain table/column in ENR-3.4.

## 2026-09-17 — ENR-3.5 Test Cleanup Empty-Scope Sequence Reset
- Corrected targeted Student Test Data Cleanup so a completely emptied ENR-3 QA scope can restart from sequence `1` on the next explicit Assign.
- Student UID and University Roll reset only when the College has no retained assigned value of that type.
- Class Roll resets only for the exact Programme Offering / Discipline `class_roll_scope_key` that has no retained assigned Enrollment.
- Identity-only cleanup still does not rewind sequences; production lifecycle behavior is unchanged.
- Identity numbering remains backed by `student_identity_sequences`, not Student/Enrollment auto-increment IDs.
- Database structure impact: NONE; no migration and no new domain table/column.


## 2026-09-21 — Faculty Allocation College-role eligibility permission fix
- Fixed College Role permission management so `college_faculty_allocation.eligible` is visible and assignable to College-owned roles by an authorized College permission manager.
- The eligibility code is treated as a College-delegable faculty-selection marker; the administrator does not need to be personally faculty-eligible in order to delegate it.
- Existing safeguards remain: only ACTIVE College-delegable permissions can be submitted, role ownership remains College-scoped, and assign/remove actions still require the corresponding College permission-management capability.
- University permission management was left unchanged as requested.
- No database/schema change.
# 2026-09-24 — Attendance Exceptions and Eligibility

- Implemented the next three Phase 14 milestones: policy-controlled Condonation, Medical/Special Exemption, and final Examination Attendance Eligibility.
- Added ADR 214, the College Eligibility screen, two auditable persistence tables, five granular permissions, cleanup dependencies and synchronized database/security/page documentation.

# 2026-09-24 — Internal Assessment Setup, Assignment and Quiz

- Implemented the first three Phase 15 milestones with Course Offering, Academic Policy, Faculty Allocation, Academic Calendar and canonical Enrollment linkage.
- Added stable publication-time student roster snapshots for later Marks Entry.

# 2026-09-24 — Mid Semester, Practical and Marks Entry

- Extended Internal Assessment with governed Mid Semester and Practical activities.
- Added frozen-roster Marks Entry with absence handling, bounds validation and audited corrections.

# 2026-09-24 — Academic Operations Navigation / Searchable Select Consistency
- Split College sidebar navigation into Course Delivery, Attendance and Assessment without changing domain ownership, routes or permissions.
- Course Delivery now contains only Course Offerings, Faculty Allocation, Rooms, Timetable and Class Scheduling.
- Attendance contains Attendance and Attendance Eligibility.
- Assessment contains Assessment Setup, Assignments, Quizzes, Mid Semester, Practical and Marks Entry; future assessment milestones remain in this group.
- Migrated Internal Assessment dynamic selectors to the shared SearchableSelect contract, including Course Offering, Assessment Component, Faculty Allocation and Published Activity; Assessment Type uses the same interaction for page consistency.
- Documented the rule for future Assessment / Attendance / Examination / Result UI work in ADR 217.
