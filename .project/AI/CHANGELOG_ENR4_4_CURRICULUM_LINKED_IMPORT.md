# ENR-4.4 — Curriculum-linked Student Import
Date: 2026-09-17
Status: OWNER QA PENDING

- Replaced standalone Discipline-only import semantics with Programme Offering → Curriculum academic resolution.
- Reused Admission academic preference resolver for import validation.
- Added Specialization and curriculum Choice-category mapping targets.
- Mandatory curriculum courses resolve automatically.
- Added enrollment-owned Curriculum/Specialization/course-choice persistence so Admission and Import converge after enrollment.
- Admission enrollment now copies its already-resolved curriculum context into Enrollment.
- Student mapping groups changed to accordion UX; Core Student is the only default-open group.
- No external CSS/UI dependency introduced; existing project UI components and Lucide icons remain authoritative.
- DB migration: `2026_09_17_150000_link_student_enrollment_academic_context.php`.
