# college_admission_form_templates

Reusable Admission Form configuration header owned by University or College.

Key fields: `university_id`, nullable `college_id`, nullable self-linked `parent_template_id`, nullable `manager_user_id`, `name`, `code`, `owner_scope_type`, `governance_mode`, `admission_mode`, `status`, audit user ids.

Rules: University scope has `college_id = NULL`; College scope has owning `college_id`; RETIRED is immutable; ACTIVE requires usable steps/fields at application layer.

College extension templates may reference an ACTIVE University-owned parent. Parent steps/fields are inherited; child steps/fields append without mutating the University base.

## 2026-08-27 governance addition
`allow_college_override` BOOLEAN NOT NULL DEFAULT FALSE
- meaningful for University-owned templates;
- when false, College extension creation from this base is prohibited;
- when true, an authorized College role may create a College-owned inherited extension;
- cannot be switched off while an ACTIVE child extension exists.
