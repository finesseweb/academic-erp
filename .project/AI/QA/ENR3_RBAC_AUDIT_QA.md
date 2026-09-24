# ENR-3.6 RBAC + Audit QA

1. Run migration and confirm `2026_09_17_120000_align_student_management_rbac` = Ran.
2. Role Management: verify one `Student Management` group contains Student Enrollment View/Enroll and Student Identity View/Manage.
3. Grant Identity View only: Student Identity sidebar/page visible; Save Rules and Assign unavailable/403.
4. Grant Identity View + Manage: Save Rules and Assign available.
5. Remove Identity View: Student Identity sidebar hidden and direct GET returns 403.
6. Enrollment View/Enroll continue under Student Management and enrollment behavior regresses cleanly.
7. Save a harmless identity-rule change and verify audit event `student.identity.rules.updated` with College scope and before/after.
8. Assign a test identity and verify audit event `student.identity.assigned` with Student Enrollment resource and before/after.
9. Verify another College cannot use these permissions against this College's Student Identity routes.
